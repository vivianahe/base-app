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
        Schema::create('state_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('participant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('state', ['Preinscrito', 'Inscrito', 'Asistente', 'Sin aforo', 'Cancelado']);
            $table->foreign('event_id')->references('id')->on('events_noved');
            $table->foreign('participant_id')->references('id')->on('participants');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_participants');
    }
};
