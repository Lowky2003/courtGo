<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\StripeSubscriptionSync;
use Illuminate\Console\Command;

/**
 * Pull each owner's Stripe subscriptions into the local database. Useful to
 * recover subscriptions that completed in Stripe but whose webhook never
 * reached the app (e.g. local dev without `stripe listen`).
 */
class SyncStripeSubscriptions extends Command
{
    protected $signature = 'billing:sync-subscriptions';

    protected $description = 'Record any Stripe subscriptions missing from the local database';

    public function handle(StripeSubscriptionSync $sync): int
    {
        if (! config('cashier.secret')) {
            $this->error('Stripe is not configured (cashier.secret is empty).');

            return self::FAILURE;
        }

        $synced = 0;

        foreach (User::whereNotNull('stripe_id')->get() as $user) {
            $n = $sync->forUser($user);

            if ($n > 0) {
                $this->line("Synced {$n} subscription(s) for {$user->email}");
            }

            $synced += $n;
        }

        $this->info("Done — synced {$synced} subscription(s).");

        return self::SUCCESS;
    }
}
