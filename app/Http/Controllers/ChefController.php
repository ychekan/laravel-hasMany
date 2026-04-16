<?php

namespace App\Http\Controllers;

use App\Models\Chef;
use Illuminate\Http\Request;

class ChefController extends Controller
{
    /**
     * Display a paginated list of all chefs with recipe counts and average prep time.
     */
    public function index()
    {
        $chefs = Chef::withCount('recipes')
            ->withAvg('recipes', 'prep_time')
            ->orderByDesc('recipes_count')
            ->paginate(10);

        return view('chefs.index', compact('chefs'));
    }

    /**
     * Display a specific chef along with their recipes, sorted by difficulty and prep time.
     */
    public function show(Chef $chef)
    {
        $chef->load(['recipes' => function ($query) {
            $query->orderBy('difficulty')->orderBy('prep_time');
        }]);

        return view('chefs.show', compact('chef'));
    }

    /**
     * Store a new recipe for a chef.
     */
    public function storeRecipe(Request $request, Chef $chef)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'prep_time'   => 'required|integer|min:1',
            'difficulty'  => 'required|in:easy,medium,hard',
        ]);

        $chef->recipes()->create($validated);

        return redirect()->route('chefs.show', $chef)->with('success', 'Recipe added successfully!');
    }
}

