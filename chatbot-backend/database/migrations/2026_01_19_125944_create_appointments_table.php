<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();

            $table->string('patient_name');
            $table->string('phone', 30);

            $table->string('service')->nullable();      // detartrage, blanchiment...
            $table->date('date');
            $table->time('time');

            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');

            $table->text('notes')->nullable();
            $table->timestamps();

            // éviter doublons (même date+heure)
            $table->unique(['date', 'time'], 'appointments_date_time_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

