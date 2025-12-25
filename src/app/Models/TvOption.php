<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TvOption extends Model
{
    protected $fillable = [
        'category',
        'code',
        'label',
        'price_yen',
        'constraints',
    ];

    protected $casts = [
        'constraints' => 'array',
    ];

    public function selectedOptions(): HasMany
    {
        return $this->hasMany(TvSelectedOption::class);
    }
}
