<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TvSelectedOption extends Model
{
    protected $fillable = [
        'tv_id',
        'tv_option_id',
        'quantity',
    ];

    public function tv(): BelongsTo
    {
        return $this->belongsTo(Tv::class);
    }

    public function tvOption(): BelongsTo
    {
        return $this->belongsTo(TvOption::class);
    }
}
