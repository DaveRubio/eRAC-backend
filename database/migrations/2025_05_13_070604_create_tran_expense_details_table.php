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
        Schema::create('tran_expense_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('appropriation_id')
                  ->constrained('tran_appropriations')
                  ->onDelete('cascade');

            $table->foreignId('particular_id')
                  ->constrained('lib_particulars')
                  ->onDelete('cascade');

            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tran_expense_details');
    }
};
