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
        Schema::create('events_noved', function (Blueprint $table) {
            $table->id();
            $table->string('hubspot_id');
            $table->string('name');
            $table->integer('capacity')->default(0);
            $table->date('date');
            $table->time('hour');
            $table->string('price')->default(0)->nullable();
            $table->enum('type_inscription', ['Manual', 'Automatic']);
            $table->enum('state', ['active', 'inactive']);
            $table->string('logo')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events_noved');
    }
};
