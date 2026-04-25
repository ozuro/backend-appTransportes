<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cash_incomes', function (Blueprint $table) {
            $table->foreignId('transport_service_id')
                ->nullable()
                ->after('company_id')
                ->constrained('transport_services')
                ->nullOnDelete();
            $table->foreignId('vehicle_id')
                ->nullable()
                ->after('transport_service_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('driver_id')
                ->nullable()
                ->after('vehicle_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('recorded_by_user_id')
                ->nullable()
                ->after('driver_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('beneficiary_type', 30)
                ->nullable()
                ->after('concept');
            $table->unsignedBigInteger('beneficiary_id')
                ->nullable()
                ->after('beneficiary_type');

            $table->index(['company_id', 'transport_service_id']);
            $table->index(['company_id', 'driver_id']);
            $table->index(['company_id', 'vehicle_id']);
            $table->index(['company_id', 'beneficiary_type', 'beneficiary_id']);
        });
    }

    public function down()
    {
        Schema::table('cash_incomes', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'transport_service_id']);
            $table->dropIndex(['company_id', 'driver_id']);
            $table->dropIndex(['company_id', 'vehicle_id']);
            $table->dropIndex(['company_id', 'beneficiary_type', 'beneficiary_id']);
            $table->dropConstrainedForeignId('transport_service_id');
            $table->dropConstrainedForeignId('vehicle_id');
            $table->dropConstrainedForeignId('driver_id');
            $table->dropConstrainedForeignId('recorded_by_user_id');
            $table->dropColumn(['beneficiary_type', 'beneficiary_id']);
        });
    }
};
