<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class GeneratesUniqueSlugs
{
    /**
     * Generate a unique slug for the given model and source value.
     */
    public function forModel(Model $model, string $source, string $column = 'slug'): string
    {
        $baseSlug = Str::slug($source);

        if ($baseSlug === '') {
            $baseSlug = Str::slug(class_basename($model)).'-'.Str::lower(Str::random(6));
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($model->newQuery()
            ->where($column, $slug)
            ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
