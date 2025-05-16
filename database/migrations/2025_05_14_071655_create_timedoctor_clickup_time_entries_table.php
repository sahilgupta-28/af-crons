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
        Schema::create('timedoctor_clickup_time_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('time_entry_id');
            $table->string('task_id');
            $table->unsignedBigInteger('workspace_id');
            $table->unsignedBigInteger('assignee_id');
            $table->longText('data');
            $table->integer('attempts')->default(0);
            $table->boolean('is_success')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timedoctor_clickup_time_entries');
    }
};
