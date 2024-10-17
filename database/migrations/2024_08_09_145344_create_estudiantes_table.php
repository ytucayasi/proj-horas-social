<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('periodo_id')->nullable();
            $table->unsignedBigInteger('escuela_id')->nullable();
            $table->char('codigo', 9)->unique();
            $table->char('dni', 8)->unique();
            $table->decimal('horas_base')->default(0);
            /* $table->char('periodo_id', 1);
            $table->char('anio_id', 1);
            $table->string('escuela_academica_id', 2); */
            $table->char('estado', 1)->default('1');
            $table->foreign('periodo_id')->references('id')->on('periodos')->onDelete('set null');
            $table->foreign('escuela_id')->references('id')->on('escuelas')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};
