<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'color',
        'icon',
        'budget',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'float',
            'created_at' => 'datetime',
        ];
    }
}
