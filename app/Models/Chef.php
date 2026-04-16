<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chef extends Model
{
    /** @use HasFactory<\Database\Factories\ChefFactory> */
    use HasFactory;

    protected $fillable = ['name', 'specialty'];

    /**
     * Get all recipes published by this chef.
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}

