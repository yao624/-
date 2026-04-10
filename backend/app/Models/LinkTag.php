<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LinkTag extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'link_tags';

    protected $fillable = [
        'link_id',
        'user_id',
        'meta_tag_option_id',
        'name',
    ];

    protected $casts = [
        'meta_tag_option_id' => 'integer',
    ];

    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class, 'link_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metaTagOption(): BelongsTo
    {
        return $this->belongsTo(MetaTagOptions::class, 'meta_tag_option_id');
    }
}
