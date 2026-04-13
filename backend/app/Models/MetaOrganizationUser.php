<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaOrganizationUser extends Model
{
    use HasFactory;

    protected $table = 'meta_organization_users';

    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'user_id',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(MetaOrganization::class, 'organization_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
