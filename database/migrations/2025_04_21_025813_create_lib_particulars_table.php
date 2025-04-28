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
Schema::create('lib_particulars', function (Blueprint $table) {
        $table->id();

        // Foreign key for 'barangay_id' that references the 'barangays' table
        $table->foreignId('barangay_id')
              ->constrained('barangays')
              ->onDelete('cascade');
        $table->string('particular_name');

        $table->date('transaction_date')->default(DB::raw('CURRENT_DATE'));
        $table->boolean('is_active')->default(true);
        // Metadata: 'created_by' foreign key reference to 'barangay_users' table
        $table->foreignId('created_by')
              ->nullable()
              ->constrained('barangay_users')
              ->onDelete('set null');
        $table->timestamps();
        $table->softDeletes();
        $table->index('barangay_id');
        $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lib_particulars');
    }
};
