<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tv extends Model
{
    protected $fillable = [
        'name',
        'maker',
        'inch',
        'resolution',
        'panel',
        'hdmi_ports',
        'has_hdr',
        'has_wifi',
    ];

    protected $casts = [
        'has_hdr' => 'boolean',
        'has_wifi' => 'boolean',
    ];

    public function selectedOptions(): HasMany
    {
        return $this->hasMany(TvSelectedOption::class);
    }
}
