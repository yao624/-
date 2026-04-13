<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImageTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'image_templates';

    protected $fillable = [
        'user_id',
        'template_name',
        'canvas_width',
        'canvas_height',
        'canvas_json',
        'dynamic_variables',
        'preview_image',
        'description',
        'status',
    ];

    protected $casts = [
        'canvas_json' => 'array',
        'dynamic_variables' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
