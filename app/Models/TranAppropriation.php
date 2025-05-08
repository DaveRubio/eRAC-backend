<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TranAppropriation extends Model
{
    protected $fillable = [
        'barangay_id',
        'budget_id',
        'expense_class_id',
        'expense_type_id',
        'expense_item_id',
        'amount',
        'transaction_date',
        'description',
        'status',
        'user_id'
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2'
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function expenseClass(): BelongsTo
    {
        return $this->belongsTo(LibExpenseClass::class);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(LibExpenseType::class);
    }

    public function expenseItem(): BelongsTo
    {
        return $this->belongsTo(LibExpenseItem::class);
    }
}
