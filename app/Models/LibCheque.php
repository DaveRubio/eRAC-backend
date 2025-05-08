<?php
// app/Models/LibCheque.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibCheque extends Model
{
    protected $table = 'lib_cheque'; // Note: matches your migration table name

    protected $fillable = [
        'bank_id',
        'cheque_number',
        'cheque_status'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(LibBank::class, 'bank_id');
    }
}
