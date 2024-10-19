<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Error extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_id',
        'file',
        'email',
        'error_description',
    ];

    public function log()
    {
        return $this->belongsTo(Log::class);
    }
}
