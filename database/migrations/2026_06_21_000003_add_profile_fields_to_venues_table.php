<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->text('announcement')->nullable()->after('amenities');
            $table->boolean('announcement_active')->default(false)->after('announcement');
            $table->date('announcement_until')->nullable()->after('announcement_active');
            $table->json('opening_hours')->nullable()->after('announcement_until');
            $table->string('pricing_note')->nullable()->after('opening_hours');
            $table->text('policy')->nullable()->after('pricing_note');
            $table->string('contact_phone')->nullable()->after('policy');
            $table->string('contact_whatsapp')->nullable()->after('contact_phone');
            $table->string('contact_email')->nullable()->after('contact_whatsapp');
            $table->string('contact_website')->nullable()->after('contact_email');
            $table->string('contact_instagram')->nullable()->after('contact_website');
            $table->string('contact_facebook')->nullable()->after('contact_instagram');
            $table->string('layout_image_path')->nullable()->after('contact_facebook');
        });
    }

    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn([
                'announcement', 'announcement_active', 'announcement_until', 'opening_hours',
                'pricing_note', 'policy', 'contact_phone', 'contact_whatsapp', 'contact_email',
                'contact_website', 'contact_instagram', 'contact_facebook', 'layout_image_path',
            ]);
        });
    }
};
