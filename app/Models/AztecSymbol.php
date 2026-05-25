<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AztecSymbol extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'sort_order',
        'name',
        'slug',
        'spanish_name',
        'deity',
        'body_area',
        'meaning',
        'service_description',
        'customer_greeting',
        'taste_profile',
        'recommended_items',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'recommended_items' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
