<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('operating_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transport_service_id')->nullable()->constrained('transport_services')->nullOnDelete();
            $table->string('category', 40);
            $table->string('payment_method', 30)->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('supplier_name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'category']);
            $table->index(['company_id', 'expense_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('operating_expenses');
    }
};
