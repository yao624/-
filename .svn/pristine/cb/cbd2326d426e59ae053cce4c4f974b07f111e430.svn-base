<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchBookmark extends BaseModel
{

    protected $fillable = [
        'user_id',
        'name',
        'search_conditions',
        'description'
    ];

    protected $casts = [
        'search_conditions' => 'array',
    ];

    /**
     * 关联用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
