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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('court_id')->constrained('courts')->cascadeOnDelete();
            $table->foreignId('session_template_id')->nullable()->constrained('session_templates')->nullOnDelete();
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('price', 8, 2);
            $table->string('status')->default('pending');         // pending|confirmed|cancelled|expired
            $table->string('payment_status')->default('unpaid');  // unpaid|paid|refunded
            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('processed_at')->nullable();         // idempotency: webhook handled
            $table->timestamps();

            $table->index(['court_id', 'booking_date']);
        });

        $this->addActiveSlotUniqueIndex();
    }

    /**
     * Add an "active-scoped" unique index so two active bookings can't share the
     * same court + date + start time. MySQL has no partial indexes (use a generated
     * column); SQLite/Postgres support a partial unique index directly.
     */
    private function addActiveSlotUniqueIndex(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE bookings ADD COLUMN active_flag TINYINT".
                " GENERATED ALWAYS AS (CASE WHEN status IN ('pending','confirmed') THEN 1 ELSE NULL END) VIRTUAL"
            );
            \Illuminate\Support\Facades\DB::statement(
                "CREATE UNIQUE INDEX uniq_active_slot ON bookings (court_id, booking_date, start_time, active_flag)"
            );
        } else {
            // SQLite / PostgreSQL partial unique index
            \Illuminate\Support\Facades\DB::statement(
                "CREATE UNIQUE INDEX uniq_active_slot ON bookings (court_id, booking_date, start_time)".
                " WHERE status IN ('pending','confirmed')"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
