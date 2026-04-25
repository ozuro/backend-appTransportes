<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('concept', 150);
            $table->text('note')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'received_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash_incomes');
    }
};
