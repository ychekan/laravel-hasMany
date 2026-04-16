<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeFactory> */
    use HasFactory;

    protected $fillable = ['chef_id', 'title', 'description', 'prep_time', 'difficulty'];

    /**
     * Get the chef who published this recipe.
     */
    public function chef(): BelongsTo
    {
        return $this->belongsTo(Chef::class);
    }
}

