<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Statistic extends Model
{


    protected $fillable = [
        'visitor_id',
        'send_date',
        'open_date',
        'opens',
        'viral_opens',
        'click_date',
        'clicks',
        'viral_clicks',
        'links',
        'ips',
        'browsers',
        'platforms',
    ];

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }
}
