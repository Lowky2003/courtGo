<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;

class StripeConnectWebhookController extends Controller
{
    /**
     * Handle Stripe Connect webhooks (delivered for connected accounts).
     * We care about `account.updated`, which tells us when an owner finished
     * onboarding and can receive payouts.
     */
    public function handle(Request $request): Response
    {
        $payload = $this->verifiedPayload($request);

        if ($payload === null) {
            return response('Invalid signature', 400);
        }

        if (($payload['type'] ?? null) === 'account.updated') {
            $account = $payload['data']['object'] ?? [];
            $accountId = $account['id'] ?? null;

            if ($accountId) {
                $ready = (bool) ($account['charges_enabled'] ?? false)
                    && (bool) ($account['payouts_enabled'] ?? false)
                    && (bool) ($account['details_submitted'] ?? false);

                User::query()
                    ->where('stripe_connect_account_id', $accountId)
                    ->update(['connect_onboarded' => $ready]);
            }
        }

        return response('Webhook handled', 200);
    }

    /**
     * Verify the Stripe signature when a Connect webhook secret is configured;
     * otherwise (e.g. in tests) accept the raw JSON body.
     *
     * @return array<string, mixed>|null  null means the signature was invalid
     */
    private function verifiedPayload(Request $request): ?array
    {
        $secret = config('services.stripe.connect_webhook_secret');

        if (! $secret) {
            return $request->json()->all();
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret,
            );
        } catch (\Throwable $e) {
            return null;
        }

        return $event->toArray();
    }
}
