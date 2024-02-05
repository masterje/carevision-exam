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
        Schema::create('user_events', function (Blueprint $table) {
            $table->biginteger('user_id')->unsigned();
            $table->biginteger('event_id')->unsigned();
        });

        Schema::table('user_events', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
        });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_events', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['event_id']);
        });

        Schema::dropIfExists('user_events');
    }
};
