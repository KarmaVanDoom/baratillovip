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
        Schema::create('registries', function (Blueprint $table) {
            $table->id();
            $table->integer('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->date('fecha_hora_ingreso');
            $table->string('color');
            $table->enum('estado', ['nuevo', 'poco uso', 'usado nn']);
            $table->integer('precio');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registries');
    }
};
