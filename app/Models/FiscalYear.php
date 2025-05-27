<?php
// app/Models/FiscalYear.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    use HasFactory;

   protected $table = 'lib_fiscal_years';

    protected $fillable = [
        'barangay_id',
        'year',
        'is_active'
    ];

    public function barangay()
    {
        return $this->belongsTo(Barangay::class);
    }

    // Will add these relationships later
    public function expenseClasses()
    {
        return $this->hasMany(ExpenseClass::class);
    }

    public function appropriations()
    {
        return $this->hasMany(Appropriation::class);
    }

    public function getYearValidationAttribute()
    {
    return $this->created_at->format('Y') == $this->year;
}
}
