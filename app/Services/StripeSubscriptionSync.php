<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;

/**
 * Pull a user's Stripe subscriptions into the local database — creating any the
 * webhook missed and updating status / cancellation date for existing ones.
 * Used on the Checkout & billing-portal return (so changes show even in local
 * dev where Stripe's webhook can't reach the app) and by the sync command.
 */
class StripeSubscriptionSync
{
    /** @return int number of subscriptions created or updated */
    public function forUser(User $user): int
    {
        if (! $user->stripe_id || ! config('cashier.secret')) {
            return 0;
        }

        $stripeSubs = Cashier::stripe()->subscriptions->all([
            'customer' => $user->stripe_id, 'status' => 'all', 'limit' => 100,
        ]);

        $count = 0;

        foreach ($stripeSubs->data as $s) {
            $endsAt = $this->endsAt($s);
            $local = $user->subscriptions()->where('stripe_id', $s->id)->first();

            if ($local) {
                $local->update(['stripe_status' => $s->status, 'ends_at' => $endsAt]);
                $count++;
            } elseif ($type = ($s->metadata['type'] ?? null)) {
                $user->subscriptions()->create([
                    'type' => $type,
                    'stripe_id' => $s->id,
                    'stripe_status' => $s->status,
                    'stripe_price' => $s->items->data[0]->price->id ?? null,
                    'quantity' => $s->items->data[0]->quantity ?? 1,
                    'ends_at' => $endsAt,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /** When the subscription ends — past for a hard cancel, future for cancel-at-period-end. */
    private function endsAt($s): ?Carbon
    {
        if ($s->status === 'canceled') {
            $ts = $s->ended_at ?? $s->canceled_at;

            return $ts ? Carbon::createFromTimestamp($ts) : Carbon::now()->subSecond();
        }

        if ($s->cancel_at_period_end && $s->cancel_at) {
            return Carbon::createFromTimestamp($s->cancel_at);
        }

        return null;
    }
}
