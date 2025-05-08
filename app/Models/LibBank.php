<?php
// app/Models/LibBank.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibBank extends Model
{

    protected $table = 'lib_banks';

    protected $fillable = [
         'bank_name',
    'status',
    'barangay_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cheques(): HasMany
    {
        return $this->hasMany(LibCheque::class, 'bank_id');
    }

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

}
