<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Media extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'media';

    protected $fillable = [
        'id',
        'project_id',
        'entity_type',
        'entity_id',
        'path',
        'disk',
        'mime',
        'checksum',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
