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
        Schema::create('email_mod', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->boolean('SMTPAuth');
            $table->string('SMTPSecure');
            $table->string('host');
            $table->string('port');
            $table->string('password');
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
        Schema::dropIfExists('email_mod');
    }
};
