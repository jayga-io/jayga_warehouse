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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->string('latitude');
            $table->string('longitude');
            $table->string('size');
            $table->string('contact_person');
            $table->string('contact_phone');
            $table->string('owner_name');
            $table->string('owner_phone');
            $table->string('owner_email')->unique();
            $table->string('total_grids');
            $table->string('grid_price_per_day');
            $table->string('status')->default('1');
            $table->string('district');
            $table->string('area');
            $table->string('is_active')->default('1');
            $table->string('warehouse_image');
            $table->integer('warehouse_type_id');
            $table->integer('admin_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
