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
        Schema::create('timedoctors', function (Blueprint $table) {
            $table->id();
            $table->string('timedoctor_company_id');
            $table->string('google_sheet_id');
            $table->string('google_sheet_range');
            $table->json('google_service_credentials')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timedoctors');
    }
};
