<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'display_name',
        'category',
        'to_base_factor',
        'base_unit',
    ];

    protected function casts(): array
    {
        return [
            'to_base_factor' => 'float',
        ];
    }
}
