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
        Schema::create('lead_appointments', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date'); // возможно стоит переименовать
            $table->integer('client_id')->unsigned();
            $table->string('sport_type')->nullable();
            $table->string('service_type')->nullable();
            $table->string('trainer')->nullable();
            $table->date('training_date');
            $table->time('training_time')->nullable();
            $table->enum('status', ['scheduled', 'cancelled', 'completed', 'no_show'])->default('scheduled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_appointments');
    }
};
