<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('trade_name');
            $table->string('legal_name')->nullable();
            $table->string('ruc', 11)->unique()->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('country_code', 2)->default('PE');
            $table->string('currency_code', 3)->default('PEN');
            $table->string('timezone')->default('America/Lima');
            $table->string('address')->nullable();
            $table->string('district')->nullable();
            $table->string('province')->nullable();
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
