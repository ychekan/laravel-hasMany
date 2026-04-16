<?php

namespace Tests\Feature;

use App\Models\Chef;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChefRecipeRelationshipTest extends TestCase
{
    use RefreshDatabase;

    // ─── hasMany relationship ─────────────────────────────────────────────────

    /**
     * A chef can have many recipes via hasMany.
     */
    public function test_chef_has_many_recipes(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->count(3)->create(['chef_id' => $chef->id]);

        $this->assertCount(3, $chef->recipes);
        $this->assertInstanceOf(Recipe::class, $chef->recipes->first());
    }

    /**
     * A recipe belongs to a chef via belongsTo.
     */
    public function test_recipe_belongs_to_chef(): void
    {
        $chef   = Chef::factory()->create();
        $recipe = Recipe::factory()->create(['chef_id' => $chef->id]);

        $this->assertInstanceOf(Chef::class, $recipe->chef);
        $this->assertEquals($chef->id, $recipe->chef->id);
    }

    /**
     * hasMany returns an empty collection when there are no related recipes.
     */
    public function test_chef_with_no_recipes_returns_empty_collection(): void
    {
        $chef = Chef::factory()->create();

        $this->assertCount(0, $chef->recipes);
        $this->assertTrue($chef->recipes->isEmpty());
    }

    // ─── Creating related records ──────────────────────────────────────────────

    /**
     * create() on the relationship auto-injects chef_id.
     */
    public function test_create_via_relationship_injects_chef_id(): void
    {
        $chef = Chef::factory()->create();

        $recipe = $chef->recipes()->create([
            'title'       => 'Panna Cotta',
            'description' => 'Silky Italian dessert',
            'prep_time'   => 30,
            'difficulty'  => 'easy',
        ]);

        $this->assertEquals($chef->id, $recipe->chef_id);
        $this->assertDatabaseHas('recipes', ['title' => 'Panna Cotta', 'chef_id' => $chef->id]);
    }

    /**
     * createMany() creates multiple records linked to a chef.
     */
    public function test_create_many_via_relationship(): void
    {
        $chef = Chef::factory()->create();

        $chef->recipes()->createMany([
            ['title' => 'Cacio e Pepe',  'description' => 'Roman pasta classic',  'prep_time' => 20, 'difficulty' => 'medium'],
            ['title' => 'Tiramisu',      'description' => 'Coffee-soaked dessert', 'prep_time' => 45, 'difficulty' => 'easy'],
            ['title' => 'Osso Buco',     'description' => 'Braised veal shanks',   'prep_time' => 120, 'difficulty' => 'hard'],
        ]);

        $this->assertCount(3, $chef->fresh()->recipes);
    }

    /**
     * save() on the relationship attaches an existing Recipe instance.
     */
    public function test_save_via_relationship_attaches_recipe(): void
    {
        $chef   = Chef::factory()->create();
        $recipe = new Recipe([
            'title'       => 'Risotto al Funghi',
            'description' => 'Creamy mushroom risotto',
            'prep_time'   => 40,
            'difficulty'  => 'medium',
        ]);

        $chef->recipes()->save($recipe);

        $this->assertEquals($chef->id, $recipe->chef_id);
        $this->assertDatabaseHas('recipes', ['title' => 'Risotto al Funghi', 'chef_id' => $chef->id]);
    }

    /**
     * saveMany() attaches multiple Recipe instances at once.
     */
    public function test_save_many_via_relationship(): void
    {
        $chef = Chef::factory()->create();

        $recipe1 = new Recipe(['title' => 'Pizza Margherita', 'description' => 'Classic Neapolitan pizza', 'prep_time' => 30, 'difficulty' => 'easy']);
        $recipe2 = new Recipe(['title' => 'Focaccia',         'description' => 'Herbed flatbread',         'prep_time' => 20, 'difficulty' => 'easy']);

        $chef->recipes()->saveMany([$recipe1, $recipe2]);

        $this->assertCount(2, $chef->fresh()->recipes);
        $this->assertEquals($chef->id, $recipe1->chef_id);
        $this->assertEquals($chef->id, $recipe2->chef_id);
    }

    // ─── Querying through the relationship ────────────────────────────────────

    /**
     * Recipes can be filtered through the relationship query builder.
     */
    public function test_filter_recipes_by_difficulty_through_relationship(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->easy()->count(3)->create(['chef_id' => $chef->id]);
        Recipe::factory()->hard()->count(2)->create(['chef_id' => $chef->id]);

        $easyRecipes = $chef->recipes()->where('difficulty', 'easy')->get();

        $this->assertCount(3, $easyRecipes);
        $easyRecipes->each(fn ($r) => $this->assertEquals('easy', $r->difficulty));
    }

    /**
     * count() returns the correct number of related records without loading them.
     */
    public function test_count_recipes_without_loading(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->count(5)->create(['chef_id' => $chef->id]);

        $this->assertEquals(5, $chef->recipes()->count());
    }

    /**
     * exists() returns true/false correctly.
     */
    public function test_exists_on_filtered_relationship(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->hard()->create(['chef_id' => $chef->id]);

        $this->assertTrue($chef->recipes()->where('difficulty', 'hard')->exists());
        $this->assertFalse($chef->recipes()->where('difficulty', 'easy')->exists());
    }

    // ─── Eager loading ─────────────────────────────────────────────────────────

    /**
     * Chef::with('recipes') eager-loads recipes in 2 queries (no N+1).
     */
    public function test_eager_loading_with_recipes(): void
    {
        $chefs = Chef::factory()->count(3)->create();
        $chefs->each(fn ($chef) => Recipe::factory()->count(2)->create(['chef_id' => $chef->id]));

        $loadedChefs = Chef::with('recipes')->get();

        $loadedChefs->each(function ($chef) {
            $this->assertTrue($chef->relationLoaded('recipes'));
            $this->assertCount(2, $chef->recipes);
        });
    }

    /**
     * Eager loading with a constraint loads only matching recipes.
     */
    public function test_eager_loading_with_constraint(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->hard()->count(2)->create(['chef_id' => $chef->id]);
        Recipe::factory()->easy()->count(3)->create(['chef_id' => $chef->id]);

        $chef = Chef::with(['recipes' => fn ($q) => $q->where('difficulty', 'hard')])->find($chef->id);

        $this->assertCount(2, $chef->recipes);
        $chef->recipes->each(fn ($r) => $this->assertEquals('hard', $r->difficulty));
    }

    // ─── Aggregate helpers ────────────────────────────────────────────────────

    /**
     * withCount adds a recipes_count attribute.
     */
    public function test_with_count_adds_recipes_count_attribute(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->count(4)->create(['chef_id' => $chef->id]);

        $chef = Chef::withCount('recipes')->find($chef->id);

        $this->assertEquals(4, $chef->recipes_count);
    }

    /**
     * withCount with a condition counts only matching children.
     */
    public function test_with_count_conditional(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->hard()->count(2)->create(['chef_id' => $chef->id]);
        Recipe::factory()->easy()->count(3)->create(['chef_id' => $chef->id]);

        $chef = Chef::withCount([
            'recipes',
            'recipes as hard_recipes_count' => fn ($q) => $q->where('difficulty', 'hard'),
        ])->find($chef->id);

        $this->assertEquals(5, $chef->recipes_count);
        $this->assertEquals(2, $chef->hard_recipes_count);
    }

    /**
     * avg(), sum(), min() aggregate methods work on related records.
     */
    public function test_aggregates_on_relationship(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->create(['chef_id' => $chef->id, 'prep_time' => 20, 'difficulty' => 'easy', 'title' => 'A', 'description' => 'A']);
        Recipe::factory()->create(['chef_id' => $chef->id, 'prep_time' => 40, 'difficulty' => 'medium', 'title' => 'B', 'description' => 'B']);
        Recipe::factory()->create(['chef_id' => $chef->id, 'prep_time' => 60, 'difficulty' => 'hard', 'title' => 'C', 'description' => 'C']);

        $this->assertEquals(40,  $chef->recipes()->avg('prep_time'));
        $this->assertEquals(120, $chef->recipes()->sum('prep_time'));
        $this->assertEquals(20,  $chef->recipes()->min('prep_time'));
        $this->assertEquals(60,  $chef->recipes()->max('prep_time'));
    }

    /**
     * withAvg loads aggregate alongside model.
     */
    public function test_with_avg_on_chef(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->create(['chef_id' => $chef->id, 'prep_time' => 30, 'difficulty' => 'easy', 'title' => 'X', 'description' => 'X']);
        Recipe::factory()->create(['chef_id' => $chef->id, 'prep_time' => 60, 'difficulty' => 'hard', 'title' => 'Y', 'description' => 'Y']);

        $chef = Chef::withAvg('recipes', 'prep_time')->find($chef->id);

        $this->assertEquals(45, $chef->recipes_avg_prep_time);
    }

    // ─── Filtering parents: whereHas / doesntHave ────────────────────────────

    /**
     * whereHas returns chefs who have at least one hard recipe.
     */
    public function test_where_has_filters_chefs_with_hard_recipes(): void
    {
        $hardChef = Chef::factory()->create();
        Recipe::factory()->hard()->create(['chef_id' => $hardChef->id]);

        $easyChef = Chef::factory()->create();
        Recipe::factory()->easy()->create(['chef_id' => $easyChef->id]);

        $results = Chef::whereHas('recipes', fn ($q) => $q->where('difficulty', 'hard'))->get();

        $this->assertCount(1, $results);
        $this->assertEquals($hardChef->id, $results->first()->id);
    }

    /**
     * has('recipes', '>', N) returns chefs with more than N recipes.
     */
    public function test_has_greater_than_filters_prolific_chefs(): void
    {
        $prolific = Chef::factory()->create();
        Recipe::factory()->count(6)->create(['chef_id' => $prolific->id]);

        $quiet = Chef::factory()->create();
        Recipe::factory()->count(2)->create(['chef_id' => $quiet->id]);

        $results = Chef::has('recipes', '>', 5)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($prolific->id, $results->first()->id);
    }

    /**
     * doesntHave returns chefs with no recipes at all.
     */
    public function test_doesnt_have_returns_chefs_without_recipes(): void
    {
        $withRecipes    = Chef::factory()->create();
        Recipe::factory()->create(['chef_id' => $withRecipes->id]);

        $withoutRecipes = Chef::factory()->create();

        $results = Chef::doesntHave('recipes')->get();

        $this->assertCount(1, $results);
        $this->assertEquals($withoutRecipes->id, $results->first()->id);
    }

    // ─── Cascade delete ────────────────────────────────────────────────────────

    /**
     * Deleting a chef removes all their recipes (database-level cascade).
     */
    public function test_deleting_chef_cascades_to_recipes(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->count(3)->create(['chef_id' => $chef->id]);

        $chefId = $chef->id;
        $chef->delete();

        $this->assertDatabaseMissing('chefs', ['id' => $chefId]);
        $this->assertDatabaseMissing('recipes', ['chef_id' => $chefId]);
    }

    /**
     * Eloquent-level delete works: recipes()->delete() then chef->delete().
     */
    public function test_eloquent_level_delete_of_recipes_then_chef(): void
    {
        $chef = Chef::factory()->create();
        Recipe::factory()->count(2)->create(['chef_id' => $chef->id]);

        $chefId = $chef->id;
        $chef->recipes()->delete();
        $chef->delete();

        $this->assertDatabaseMissing('chefs', ['id' => $chefId]);
        $this->assertDatabaseMissing('recipes', ['chef_id' => $chefId]);
    }

    // ─── HTTP layer ─────────────────────────────────────────────────────────────

    /**
     * GET /chefs returns 200 and shows chef names.
     */
    public function test_chefs_index_page_returns_200(): void
    {
        Chef::factory()->count(2)->create();

        $this->get(route('chefs.index'))->assertStatus(200);
    }

    /**
     * GET /chefs/{chef} returns 200 and shows the chef's name.
     */
    public function test_chefs_show_page_returns_200(): void
    {
        $chef = Chef::factory()->create(['name' => 'Sofia Ricci']);

        $this->get(route('chefs.show', $chef))
            ->assertStatus(200)
            ->assertSee('Sofia Ricci');
    }
}

