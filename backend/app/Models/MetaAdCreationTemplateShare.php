<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaAdCreationTemplateShare extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $table = 'adtemplate_shares';

    protected $fillable = [
        'adtemplate_id',
        'user_id',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(MetaAdCreationTemplate::class, 'adtemplate_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
