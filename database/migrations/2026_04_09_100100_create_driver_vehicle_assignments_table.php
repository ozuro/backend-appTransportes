<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('driver_vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('assignment_type', 30)->default('full_time');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'driver_id', 'is_active']);
            $table->index(['company_id', 'vehicle_id', 'is_active']);
            $table->index(['company_id', 'assignment_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('driver_vehicle_assignments');
    }
};
