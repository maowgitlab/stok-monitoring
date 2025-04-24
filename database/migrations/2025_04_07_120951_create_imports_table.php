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
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->date('tanggal_import');
            $table->enum('status', ['success', 'processing', 'failed'])->default('processing');
            $table->integer('jumlah_data')->unsigned()->default(0);
            $table->timestamps();
            
            // Index untuk performa query
            $table->index('tanggal_import');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
