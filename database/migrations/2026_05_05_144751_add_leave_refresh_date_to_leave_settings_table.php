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
        Schema::table('leave_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('leave_refresh_day')->after('maximum_allowance')->default(1);
            $table->unsignedTinyInteger('leave_refresh_month')->after('leave_refresh_day')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_settings', function (Blueprint $table) {
            $table->dropColumn('leave_refresh_day');
            $table->dropColumn('leave_refresh_month');
        });
    }
};
