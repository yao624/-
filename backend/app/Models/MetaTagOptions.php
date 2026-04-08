<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaTagOptions extends Model
{
    use HasFactory;

    protected $table = 'meta_tag_options';

    protected $fillable = [
        'tag_id',
        'parent_id',
        'name',
        'description',
        'url',
        'remark1',
        'remark2',
    ];

    protected $casts = [
        'tag_id' => 'integer',
    ];

    public function metaTag()
    {
        return $this->belongsTo(MetaTags::class, 'tag_id');
    }
}
