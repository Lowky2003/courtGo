<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\StripeConnectService;
use App\Services\StripeSubscriptionSync;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    /** Whether Stripe keys are configured (so we never call Stripe unconfigured). */
    private function stripeConfigured(): bool
    {
        return (bool) config('cashier.secret');
    }

    /** Start the monthly subscription for ONE venue via Stripe Checkout. */
    public function subscribe(Request $request, Venue $venue)
    {
        abort_unless($venue->owner_id === $request->user()->id, 403);

        $user = $request->user();

        // Gated: a venue must be admin-approved (documents verified) before the
        // owner can start paying for it — don't charge for a listing we haven't
        // cleared to go live.
        if (! $venue->isApproved()) {
            return redirect()->route('owner.billing')->with(
                'stripe_error',
                $venue->name.' must be approved by an admin before you can subscribe to it. Upload its verification documents to get approved.'
            );
        }

        // Already subscribed — don't start a second checkout (would double-bill).
        if ($user->subscribed($venue->subscriptionType())) {
            return redirect()->route('owner.billing');
        }

        $priceId = config('services.stripe.price_id');

        if (! $this->stripeConfigured() || ! $priceId) {
            return redirect()->route('owner.billing')->with(
                'stripe_error',
                'Stripe is not set up yet — add your test keys and a Price ID (see docs/STRIPE-SETUP.md).'
            );
        }

        return $user->newSubscription($venue->subscriptionType(), $priceId)->checkout([
            'success_url' => route('owner.billing.subscribed', $venue).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('owner.billing').'?checkout=cancel',
            // Names the subscription in Stripe (dashboard + invoices) per venue.
            // array_merge_recursive keeps Cashier's own subscription_data.metadata.
            'subscription_data' => ['description' => 'CourtGo Owner Plan: '.$venue->name],
        ]);
    }

    /**
     * Owner returns from a successful Checkout. Record the subscription straight
     * from the completed session so it shows immediately — even in local dev
     * where Stripe's webhook can't reach the app. Idempotent with the webhook.
     */
    public function subscribed(Request $request, Venue $venue)
    {
        abort_unless($venue->owner_id === $request->user()->id, 403);

        $user = $request->user();
        $sessionId = $request->query('session_id');

        if ($sessionId && $this->stripeConfigured() && ! $user->subscribed($venue->subscriptionType())) {
            try {
                $session = \Laravel\Cashier\Cashier::stripe()->checkout->sessions->retrieve($sessionId);

                if ($session->subscription
                    && ! $user->subscriptions()->where('stripe_id', $session->subscription)->exists()) {
                    $stripeSub = \Laravel\Cashier\Cashier::stripe()->subscriptions->retrieve($session->subscription);

                    $user->subscriptions()->create([
                        'type' => $venue->subscriptionType(),
                        'stripe_id' => $stripeSub->id,
                        'stripe_status' => $stripeSub->status,
                        'stripe_price' => $stripeSub->items->data[0]->price->id ?? null,
                        'quantity' => $stripeSub->items->data[0]->quantity ?? 1,
                        'ends_at' => null,
                    ]);
                }
            } catch (\Throwable $e) {
                report($e); // don't break the return page if Stripe is unreachable
            }
        }

        $status = $user->subscribed($venue->subscriptionType())
            ? $venue->name.' is now subscribed.'
            : 'Thanks! Your subscription is being activated — refresh in a moment if it isn\'t shown yet.';

        return redirect()->route('owner.billing')->with('status', $status);
    }

    /** Open Stripe's billing portal to manage/cancel the subscription. */
    public function billingPortal(Request $request)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing');
        }

        try {
            // Return to the sync handler so a change made in the portal (e.g. a
            // cancellation) is reflected even without the webhook reaching us.
            return $request->user()->redirectToBillingPortal(route('owner.billing.portal.return'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('owner.billing')->with(
                'stripe_error',
                "Couldn't open the Stripe billing portal. In test mode you have to enable it once: Stripe Dashboard → Settings → Billing → Customer portal → Activate."
            );
        }
    }

    /** Owner returns from the Stripe billing portal — sync any changes they made. */
    public function portalReturn(Request $request, StripeSubscriptionSync $sync)
    {
        try {
            $sync->forUser($request->user());
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('owner.billing');
    }

    /** Send the owner to Stripe Connect onboarding to connect their bank. */
    public function connect(Request $request, StripeConnectService $connect)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing')->with(
                'stripe_error',
                'Stripe is not set up yet — add your test keys (see docs/STRIPE-SETUP.md).'
            );
        }

        $url = $connect->onboardingUrl(
            $request->user(),
            route('owner.connect.return'),
            route('owner.connect.refresh'),
        );

        return redirect($url);
    }

    /** Owner returns from Stripe onboarding — re-check their status. */
    public function connectReturn(Request $request, StripeConnectService $connect)
    {
        if ($this->stripeConfigured()) {
            $connect->refreshStatus($request->user());
        }

        return redirect()->route('owner.billing');
    }

    /** The onboarding link expired — make a fresh one. */
    public function connectRefresh(Request $request, StripeConnectService $connect)
    {
        if (! $this->stripeConfigured()) {
            return redirect()->route('owner.billing');
        }

        $url = $connect->onboardingUrl(
            $request->user(),
            route('owner.connect.return'),
            route('owner.connect.refresh'),
        );

        return redirect($url);
    }
}
