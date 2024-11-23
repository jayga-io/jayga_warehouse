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
        Schema::create('grids', function (Blueprint $table) {
            $table->id();
            $table->id('warehouse_id');
            $table->string('grid_code');
            $table->string('size');
            $table->string('has_rack')->default('0');
            $table->string('rack_multiplier');
            $table->string('status')->default('1');
            $table->string('type')->nullable();
            $table->string('is_occupied')->default('0');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grids');
    }
};
