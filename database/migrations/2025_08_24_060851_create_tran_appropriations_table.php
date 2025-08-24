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
        Schema::create('tran_appropriations', function (Blueprint $table) {
             $table->id();
    $table->foreignId('barangay_id')->constrained()->onDelete('cascade');
    $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
    $table->foreignId('expense_class_id')->nullable()->constrained('lib_expense_classes')->nullOnDelete();
    $table->foreignId('expense_type_id')->nullable()->constrained('lib_expense_types')->nullOnDelete();
    $table->foreignId('expense_item_id')->nullable()->constrained('lib_expense_items')->nullOnDelete();
    $table->decimal('amount', 15, 2);
    $table->date('transaction_date');
    $table->enum('status', ['uncommitted', 'committed'])->default('uncommitted');
    $table->foreignId('user_id')->constrained('barangay_users')->onDelete('cascade');
    $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tran_appropriations');
    }
};
