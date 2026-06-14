<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_connect_account_id')->nullable()->after('role');
            $table->boolean('connect_onboarded')->default(false)->after('stripe_connect_account_id');
            $table->string('business_registration_number')->nullable()->after('connect_onboarded');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_connect_account_id',
                'connect_onboarded',
                'business_registration_number',
            ]);
        });
    }
};
