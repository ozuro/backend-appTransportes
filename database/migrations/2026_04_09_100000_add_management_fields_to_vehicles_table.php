<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('company_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('management_mode', 30)
                ->default('company_fleet')
                ->after('operational_status');

            $table->index(['company_id', 'owner_user_id']);
            $table->index(['company_id', 'management_mode']);
        });
    }

    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropIndex(['company_id', 'owner_user_id']);
            $table->dropIndex(['company_id', 'management_mode']);
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropColumn('management_mode');
        });
    }
};
