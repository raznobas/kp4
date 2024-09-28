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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('name');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('id');
        });
        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('client_id');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('client_id');
        });
        Schema::table('category_costs', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('cost');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedBigInteger('director_id')->nullable()->after('type');
        });

        // Добавляем внешние ключи
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('category_costs', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('director_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
        Schema::table('category_costs', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
        Schema::table('lead_appointments', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropForeign(['director_id']);
            $table->dropColumn('director_id');
        });
    }
};
