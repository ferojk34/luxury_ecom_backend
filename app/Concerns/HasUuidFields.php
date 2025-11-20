<?php

namespace App\Concerns;

use Illuminate\Support\Str;

trait HasUuidFields
{
    /**
     * List of UUID fields to auto-generate.
     * Example for product:
     * protected array $uuidFields = ['id', 'category_id', 'brand_id'];
     */
    protected array $uuidFields = ['id'];

    /**
     * Boot the trait to generate UUIDs for any fields defined.
     */
    protected static function bootHasUuidFields()
    {
        static::creating(function ($model) {
            foreach ($model->uuidFields as $field) {
                if (empty($model->{$field})) {
                    $model->{$field} = (string) Str::uuid();
                }
            }
        });
    }
}
