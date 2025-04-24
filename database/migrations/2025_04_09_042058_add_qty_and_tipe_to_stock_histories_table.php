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
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->decimal('qty', 12, 2)->default(0)->after('stok');
            $table->enum('tipe', ['IN', 'OUT'])->nullable()->after('qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $table->dropColumn(['qty', 'tipe']);
        });
    }
};
