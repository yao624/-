<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FbPageForm extends BaseModel
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'source_id',
        'locale',
        'name',
        'status',
        'created_time',
        'thank_you_page',
        'questions',
        'privacy_policy_url',
        'legal_content',
        'follow_up_action_url',
        'leads_count',
        'page_source_id',
        'page_name',
        'notes',
    ];

    protected $casts = [
        'thank_you_page' => 'array',
        'questions' => 'array',
        'legal_content' => 'array',
        'created_time' => 'datetime'
    ];

    protected $searchAction = [
        'page_source_id' => '=',
        'page_name' => '=',
        'follow_up_action_url' => '=',
    ];



    public function page()
    {
        return $this->belongsTo(FbPage::class, 'page_source_id', 'source_id');
    }
}
