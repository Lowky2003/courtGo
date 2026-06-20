<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slots booked in the same action share a booking_group, so "My Bookings"
     * only merges slots that were actually booked together (not unrelated
     * back-to-back bookings).
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->uuid('booking_group')->nullable()->after('session_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_group');
        });
    }
};
