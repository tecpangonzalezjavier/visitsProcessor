<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    // Los campos que pueden ser asignados masivamente
    protected $fillable = [
        'file_name',
        'successful_records',
        'processing_date',
        'error_records',
        'processed_at', // Por ejemplo, si tienes una fecha de procesamiento
    ];

    // Relación de uno a muchos (si otros modelos necesitan referencia a Log)
    public function unprocessedItems()
    {
        return $this->hasMany(UnprocessedItem::class);
    }

    // Definir un accessor o mutator si se requiere alguna transformación
    public function setFileNameAttribute($value)
    {
        $this->attributes['file_name'] = strtolower($value);
    }
}
