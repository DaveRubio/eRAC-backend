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
        Schema::create('lib_banks', function (Blueprint $table) {
           $table->id();
            $table->foreignId('barangay_id')->constrained()->onDelete('cascade');
            $table->string('bank_name');
            $table->enum('status', ['available', 'consumed'])->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lib_banks');
    }
};
