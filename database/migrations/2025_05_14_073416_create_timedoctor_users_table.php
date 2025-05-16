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
        Schema::create('timedoctor_users', function (Blueprint $table) {
            $table->id();
            $table->string('td_user_id');
            $table->string('td_user_name');
            $table->string('td_job_title');
            $table->string('email');
            $table->string('profile_time_zone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timedoctor_users');
    }
};
