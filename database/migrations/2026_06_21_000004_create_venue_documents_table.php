<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venue_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venue_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // a key from config('courtgo.verification')
            $table->string('path');
            $table->string('original_name');
            $table->timestamps();

            $table->index(['venue_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venue_documents');
    }
};
