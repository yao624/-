<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaUserMenu extends Model
{
    use HasFactory;

    protected $table = 'meta_user_menus';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'menu_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'menu_id' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MetaPermission::class, 'menu_id');
    }
}
