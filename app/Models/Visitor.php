<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'first_visit_date',
        'last_visit_date',
        'total_visits',
        'current_year_visits',
        'current_month_visits',
    ];

    public function statistics()
    {
        return $this->hasMany(Statistic::class);
    }
}
