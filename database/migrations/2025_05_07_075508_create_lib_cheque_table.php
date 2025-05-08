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
        Schema::create('lib_cheque', function (Blueprint $table) {
        $table->id();
        $table->foreignId('bank_id')->constrained('lib_banks');
        $table->string('cheque_number');
        $table->enum('cheque_status', ['unused', 'used'])->default('unused');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lib_cheque');
    }
};
