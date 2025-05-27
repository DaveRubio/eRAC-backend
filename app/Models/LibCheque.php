<?php
// app/Models/LibCheque.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LibCheque extends Model
{
    protected $table = 'lib_cheque';

    protected $fillable = [
        'bank_id',
        'booklet_id',
        'cheque_number',
        'cheque_status'
    ];

    public function bank(): BelongsTo
    {
        return $this->belongsTo(LibBank::class);
    }

    public function booklet(): BelongsTo
    {
        return $this->belongsTo(LibBooklet::class);
    }
}
