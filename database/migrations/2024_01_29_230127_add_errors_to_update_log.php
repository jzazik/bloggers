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
        foreach (['popovich', 'kochfit'] as $connection) {
            Schema::connection($connection)->table('update_logs', function (Blueprint $table) {
                $table->integer('errors')->after('next_start_at')->nullable();
                $table->json('error_details')->after('errors')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['popovich', 'kochfit'] as $connection) {
            Schema::connection($connection)->table('update_logs', function (Blueprint $table) {
                $table->dropColumn(['errors', 'error_details']);
            });
        }
    }
};
