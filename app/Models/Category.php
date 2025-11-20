<?php

namespace App\Models;

use App\Concerns\HasUuidFields;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasUuidFields;

    protected $keyType = 'string';
    public $incrementing = false;

    protected array $uuidFields = ['id'];

    protected $fillable = [
        'parent_id',
        'title',
        'slug',
        'image',
        'sort_order',
        'content',
        'meta_title',
        'meta_keywords',
        'meta_desc',
        'publish_status',
    ];
}
