<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Approval is now per-venue (venues.approved_at), so the owner-account
     * approval column is no longer used. Runs AFTER the venues backfill so
     * that migration could still read it.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable();
        });

        // Best-effort restore (not a bit-for-bit inverse): re-approve any owner
        // that has at least one approved venue, so rolling back doesn't take a
        // previously-live owner offline. Owners whose approval existed only at
        // the account level (never carried to a venue) can't be reconstructed.
        DB::table('users')
            ->whereIn('id', DB::table('venues')->whereNotNull('approved_at')->pluck('owner_id'))
            ->update(['approved_at' => now()]);
    }
};
