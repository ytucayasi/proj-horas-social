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
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->string('action'); // CREATE, UPDATE, DELETE, etc
            $table->string('model_type'); // Nombre del modelo afectado
            $table->unsignedBigInteger('model_id'); // ID del registro afectado
            $table->text('old_values')->nullable(); // Valores anteriores en JSON
            $table->text('new_values')->nullable(); // Valores nuevos en JSON
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logs');
    }
};
