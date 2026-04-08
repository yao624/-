<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetaMediaMaterial extends Model
{
    use HasFactory;

    protected $table = 'meta_media_materials';

    protected $fillable = [
        'material_id',
        'name',
        'channel',
        'use_account',
        'belong_account',
        'size',
        'duration',
        'shape',
        'format',
        'source',
        'reject_info',
        'material_note',
    ];

    protected $casts = [
        'duration' => 'decimal:2',
        'create_time' => 'datetime',
    ];

    public $timestamps = false;

    public function material()
    {
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function useAccount()
    {
        return $this->belongsTo(FbAdAccount::class, 'use_account', 'id');
    }

    public function belongAccount()
    {
        return $this->belongsTo(FbAdAccount::class, 'belong_account', 'id');
    }
}
