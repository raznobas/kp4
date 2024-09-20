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
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Имя (обязательное)
            $table->string('surname')->nullable(); // Фамилия
            $table->string('patronymic')->nullable(); // Отчество
            $table->date('birthdate')->nullable(); // Год рождения
            $table->string('workplace')->nullable(); // Место работы
            $table->string('phone')->nullable(); // Телефон
            $table->string('email')->nullable(); // Почта
            $table->string('telegram')->nullable(); // Телеграм
            $table->string('instagram')->nullable(); // Инстаграм
            $table->string('address')->nullable(); // Адрес
            $table->enum('gender', ['male', 'female'])->nullable(); // Пол
            $table->string('ad_source')->nullable(); // Реклама (источник)
            $table->boolean('is_lead'); // Лид или клиент
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
