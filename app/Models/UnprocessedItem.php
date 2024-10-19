<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnprocessedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_id',
        'file',
        'email',
        'reason_for_failure',
        'reprocess_attempted',
    ];

    public function log()
    {
        return $this->belongsTo(Log::class);
    }
    public function processedResult()
    {
        return $this->hasOne(ProcessedResult::class);
    }
}
