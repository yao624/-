<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MetaAdCreationTemplate extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'meta_ad_creation_templates';

    protected $fillable = [
        'user_id',
        'fb_ad_account_id',
        'name',
        'description',
        'form_data',
        'meta_counts',
    ];

    protected $casts = [
        'form_data' => 'array',
        'meta_counts' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sharedWith(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meta_ad_creation_template_shares', 'template_id', 'user_id')
            ->whereNull('meta_ad_creation_template_shares.deleted_at')
            ->withTimestamps();
    }
}
