<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            // Null = pending admin approval; a timestamp = approved and visible to customers.
            $table->timestamp('approved_at')->nullable()->after('image_path');
        });

        // Approval used to live on the owner account. Carry it forward to each
        // existing venue so anything that was already live stays live: a venue
        // is approved if its owner was approved. (Runs before the owner column
        // is dropped, so users.approved_at is still readable here.)
        DB::table('venues')
            ->whereIn('owner_id', DB::table('users')->whereNotNull('approved_at')->pluck('id'))
            ->update(['approved_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn('approved_at');
        });
    }
};
