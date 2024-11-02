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
        Schema::table('email_mod', function (Blueprint $table) {
            $table->string('from')->after('password');
            $table->string('api')->after('from');
            $table->string('token')->after('api');
            $table->string('view')->after('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_mod', function (Blueprint $table) {
            $table->dropColumn('from');
            $table->dropColumn('api');
            $table->dropColumn('token');
            $table->dropColumn('view');
        });
    }
};
