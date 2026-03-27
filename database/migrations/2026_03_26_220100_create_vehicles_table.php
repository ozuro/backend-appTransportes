<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('unit_type', 30);
            $table->string('plate', 15)->unique();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->decimal('capacity_value', 10, 2)->nullable();
            $table->string('capacity_unit', 20)->nullable();
            $table->integer('mileage')->nullable();
            $table->date('soat_expires_at')->nullable();
            $table->date('technical_review_expires_at')->nullable();
            $table->string('operational_status', 30)->default('active');
            $table->decimal('estimated_cost_per_km', 10, 2)->nullable();
            $table->decimal('estimated_cost_per_service', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'unit_type']);
            $table->index(['company_id', 'operational_status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('vehicles');
    }
};
