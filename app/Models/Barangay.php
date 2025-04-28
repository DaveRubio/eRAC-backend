<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Barangay extends Model

{
    use Notifiable, HasFactory, HasApiTokens;

    protected $fillable = ['name'];

    public function users()
    {
        return $this->hasMany(BarangayUser::class);
    }

    // Helper to get all barangays for dropdown
    public static function dropdown()
    {
        return self::orderBy('name')->pluck('name', 'id');
    }
}
