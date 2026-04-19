<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void{
        Schema::table('conversations', function (Blueprint $table) {
        $table->string('step')->nullable();
        $table->json('data')->nullable();
    });
    Schema::create('messages', function (Blueprint $table) {
        $table->id();
        $table->foreignId('conversation_id')
              ->constrained()
              ->onDelete('cascade');

        $table->enum('sender', ['user', 'bot']);
        $table->text('content');
        $table->timestamps();
    });
     }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
