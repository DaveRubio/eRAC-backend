<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LibExpenseItem extends Model
{
     protected $table = 'lib_expense_items';

      protected $fillable = [
        'expense_type_id',
        'name',
        'order',
    ];

    // Relationships
    public function expenseType()
    {
        return $this->belongsTo(LibExpenseType::class, 'expense_type_id');
    }

    // Scopes
    public function scopeForType(Builder $query, $typeId): Builder
    {
        return $query->where('expense_type_id', $typeId);
    }

    public function scopeOrdered(Builder $query, string $direction = 'asc'): Builder
    {
        return $query->orderBy('order', $direction);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
