<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Cashier;

/**
 * Pull each owner's Stripe subscriptions into the local database. Useful to
 * recover subscriptions that completed in Stripe but whose webhook never
 * reached the app (e.g. local dev without `stripe listen`).
 */
class SyncStripeSubscriptions extends Command
{
    protected $signature = 'billing:sync-subscriptions';

    protected $description = 'Record any Stripe subscriptions missing from the local database';

    public function handle(): int
    {
        if (! config('cashier.secret')) {
            $this->error('Stripe is not configured (cashier.secret is empty).');

            return self::FAILURE;
        }

        $synced = 0;

        foreach (User::whereNotNull('stripe_id')->get() as $user) {
            $stripeSubs = Cashier::stripe()->subscriptions->all([
                'customer' => $user->stripe_id, 'status' => 'all', 'limit' => 100,
            ]);

            foreach ($stripeSubs->data as $s) {
                $type = $s->metadata['type'] ?? null;

                if (! $type || $user->subscriptions()->where('stripe_id', $s->id)->exists()) {
                    continue;
                }

                $user->subscriptions()->create([
                    'type' => $type,
                    'stripe_id' => $s->id,
                    'stripe_status' => $s->status,
                    'stripe_price' => $s->items->data[0]->price->id ?? null,
                    'quantity' => $s->items->data[0]->quantity ?? 1,
                    'ends_at' => $s->cancel_at_period_end ? Carbon::createFromTimestamp($s->current_period_end) : null,
                ]);

                $synced++;
                $this->line("Synced {$type} ({$s->id}) for {$user->email}");
            }
        }

        $this->info("Done — synced {$synced} subscription(s).");

        return self::SUCCESS;
    }
}
