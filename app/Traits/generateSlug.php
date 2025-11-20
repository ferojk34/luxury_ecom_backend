<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait GenerateSlug
{
    public function generateSlug(string $modelClass, string $value): string
    {
        $slug = Str::slug($value);
        $original = $slug;

        $count = 1;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $original . '-' . $count++;
        }

        return $slug;
    }
}
