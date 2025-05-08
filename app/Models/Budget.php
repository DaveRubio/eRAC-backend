<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'barangay_id',
        'fiscal_year_id',
        'start_date',
        'end_date',
        'description',
        'original_amount',
        'current_amount',
        'augmentation',
        'return_amount',
        'user_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'original_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'augmentation' => 'decimal:2',
        'return_amount' => 'decimal:2'
    ];

    public function tranAppropriations(): HasMany
    {
        return $this->hasMany(TranAppropriation::class);
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(Barangay::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
