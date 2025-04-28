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
        // Expense Classes Table
    Schema::create('lib_expense_classes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('barangay_id')->constrained()->onDelete('cascade');

        // Explicitly reference lib_fiscal_years table
        $table->foreignId('fiscal_year_id')
              ->constrained('lib_fiscal_years'); // Specify the correct table name
        $table->string('name');
        $table->integer('order')->default(0);
        $table->timestamps();

        $table->unique(['barangay_id', 'fiscal_year_id', 'name']);
    });

    // Expense Types Table
    Schema::create('lib_expense_types', function (Blueprint $table) {
        $table->id();
        $table->foreignId('expense_class_id')
              ->constrained('lib_expense_classes')
              ->onDelete('cascade');
        $table->string('name');
        $table->integer('order')->default(0);
        $table->timestamps();

        $table->unique(['expense_class_id', 'name']);
    });

    // Expense Items Table
    Schema::create('lib_expense_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('expense_type_id')
              ->constrained('lib_expense_types')
              ->onDelete('cascade');
        $table->string('name');
        $table->integer('order')->default(0);
        $table->timestamps();

        $table->unique(['expense_type_id', 'name']);
    });
    }

    public function down()
    {
        Schema::dropIfExists('lib_expense_items');
        Schema::dropIfExists('lib_expense_types');
        Schema::dropIfExists('lib_expense_classes');
    }
};
