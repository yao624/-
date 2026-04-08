<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaAutoRuleTemplate extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'description',
        'icon',
        'color',
        'monitoring_object',
        'is_anti_fraud',
        'config',
    ];

    protected $casts = [
        'config' => 'array',
        'is_anti_fraud' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(MetaAutoRule::class, 'template_id');
    }
}
