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
        Schema::create('work_days', function (Blueprint $table) {
            $table->id();
            $table->string('day');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('work_days')->insert([
            [
                'day' => 'Monday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Tuesday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Wednesday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Thursday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Friday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Saturday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'day' => 'Sunday',
                'active' => '1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_days');
    }
};
