<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('electronic_billing_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('environment', 20)->default('beta');
            $table->string('sol_user')->nullable();
            $table->string('sol_password')->nullable();
            $table->string('certificate_path')->nullable();
            $table->string('certificate_password')->nullable();
            $table->string('office_ubigeo', 6)->nullable();
            $table->string('office_address')->nullable();
            $table->string('office_urbanization')->nullable();
            $table->string('office_district')->nullable();
            $table->string('office_province')->nullable();
            $table->string('office_department')->nullable();
            $table->string('office_country_code', 2)->default('PE');
            $table->string('invoice_series', 4)->default('F001');
            $table->string('receipt_series', 4)->default('B001');
            $table->boolean('is_active')->default(false);
            $table->json('extra_settings')->nullable();
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('electronic_billing_configs');
    }
};
