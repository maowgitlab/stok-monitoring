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
        Schema::create('cold_storages', function (Blueprint $table) {
            $table->id();
            $table->string('item_name');
            $table->unsignedInteger('cold_storage_qty');
            $table->timestamps();
            $table->unique('item_name'); // Pastikan item_name unik
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cold_storage');
    }
};
