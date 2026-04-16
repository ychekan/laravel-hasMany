# Laravel hasMany: Building Real Relationships Between Your Models

When you build a web application, your data rarely lives in isolation. A bookstore has books. A blog has posts. A team has members. These are **one-to-many relationships** — one entity owns a collection of others. Laravel's `hasMany` is the elegant tool that wires this up, letting you navigate between related models as naturally as calling a method.

This article digs into `hasMany` from the ground up, using a consistent **real-world scenario**: a cooking platform where **Chefs** publish **Recipes**. By the end, you'll understand not just how `hasMany` works, but *why* it works the way it does.

---

## The Scenario: Chefs and Recipes

Our cooking platform has two core entities:

- A **Chef** can create many **Recipes**
- Each **Recipe** belongs to exactly one **Chef**

This is a textbook one-to-many relationship. Let's build it step by step.

---

## Step 1 — Database Migrations

Laravel's relationship system relies on a **foreign key** in the child table. In our case, the `recipes` table must store a `chef_id` column pointing back to the `chefs` table.

```php
// database/migrations/xxxx_create_chefs_table.php
Schema::create('chefs', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('specialty'); // e.g. "Italian", "Pastry", "Sushi"
    $table->timestamps();
});

// database/migrations/xxxx_create_recipes_table.php
Schema::create('recipes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('chef_id')->constrained()->onDelete('cascade');
    $table->string('title');
    $table->text('description');
    $table->integer('prep_time'); // in minutes
    $table->enum('difficulty', ['easy', 'medium', 'hard']);
    $table->timestamps();
});
```

The `foreignId('chef_id')->constrained()` call is a shorthand that:
- Creates a `chef_id` column of type `UNSIGNED BIGINT`
- Adds a foreign key constraint referencing `chefs.id`
- The `onDelete('cascade')` ensures that if a chef is deleted, all their recipes are removed automatically

---

## Step 2 — Defining the hasMany Relationship

Now open your `Chef` model and define the relationship:

```php
// app/Models/Chef.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chef extends Model
{
    protected $fillable = ['name', 'specialty'];

    /**
     * Get all recipes published by this chef.
     */
    public function recipes(): HasMany
    {
        return $this->hasMany(Recipe::class);
    }
}
```

And on the `Recipe` model, define the inverse:

```php
// app/Models/Recipe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recipe extends Model
{
    protected $fillable = ['chef_id', 'title', 'description', 'prep_time', 'difficulty'];

    /**
     * Get the chef who published this recipe.
     */
    public function chef(): BelongsTo
    {
        return $this->belongsTo(Chef::class);
    }
}
```

> **Note:** `hasMany` is always defined on the *parent* model (Chef). `belongsTo` is defined on the *child* (Recipe). They are two sides of the same relationship.

---

## Step 3 — How hasMany Works Under the Hood

When you call `$chef->recipes`, Laravel runs this SQL behind the scenes:

```sql
SELECT * FROM recipes WHERE chef_id = {chef's id};
```

Laravel figures out `chef_id` automatically using **naming conventions**:
- It takes the model name: `Chef`
- Converts it to snake_case: `chef`
- Appends `_id`: `chef_id`

If your foreign key has a different name, pass it explicitly:

```php
// Custom foreign key
return $this->hasMany(Recipe::class, 'author_id');

// Custom foreign key AND local key
return $this->hasMany(Recipe::class, 'author_id', 'uuid');
```

---

## Step 4 — Seeding Sample Data

Let's populate the database to have something to work with:

```php
// database/seeders/DatabaseSeeder.php

use App\Models\Chef;
use App\Models\Recipe;

Chef::factory()->create([
    'name' => 'Sofia Ricci',
    'specialty' => 'Italian',
])->each(function ($chef) {
    Recipe::factory()->count(4)->create(['chef_id' => $chef->id]);
});

Chef::factory()->create([
    'name' => 'Kenji Mori',
    'specialty' => 'Japanese',
])->each(function ($chef) {
    Recipe::factory()->count(3)->create(['chef_id' => $chef->id]);
});
```

Or use Eloquent directly in a seeder:

```php
$sofia = Chef::create(['name' => 'Sofia Ricci', 'specialty' => 'Italian']);

$sofia->recipes()->createMany([
    ['title' => 'Cacio e Pepe',      'description' => 'Roman pasta classic', 'prep_time' => 20, 'difficulty' => 'medium'],
    ['title' => 'Tiramisu',          'description' => 'Coffee-soaked dessert', 'prep_time' => 45, 'difficulty' => 'easy'],
    ['title' => 'Osso Buco',         'description' => 'Braised veal shanks', 'prep_time' => 120, 'difficulty' => 'hard'],
]);
```

Notice `createMany()` — a convenient method that attaches the `chef_id` automatically to each recipe.

---

## Step 5 — Accessing the Relationship

### Accessing as a property (lazy loading)

```php
$chef = Chef::find(1);

// Returns a Collection of Recipe models
$recipes = $chef->recipes;

foreach ($recipes as $recipe) {
    echo "{$recipe->title} ({$recipe->difficulty}) — {$recipe->prep_time} min\n";
}
```

### Accessing as a method (query builder)

Calling `recipes()` as a method returns a query builder you can chain:

```php
$chef = Chef::find(1);

// Only easy recipes, sorted by prep time
$quickRecipes = $chef->recipes()
    ->where('difficulty', 'easy')
    ->orderBy('prep_time')
    ->get();

// Count without loading all records
$totalRecipes = $chef->recipes()->count();

// Check if chef has any hard recipes
$hasHardRecipes = $chef->recipes()->where('difficulty', 'hard')->exists();
```

---

## Step 6 — Eager Loading (Solving the N+1 Problem)

This is the most important performance concept when using relationships. Consider this loop:

```php
// ❌ BAD — triggers N+1 queries
$chefs = Chef::all(); // 1 query

foreach ($chefs as $chef) {
    // 1 query per chef — so N additional queries!
    echo $chef->recipes->count();
}
```

If you have 50 chefs, you're running 51 queries. Fix it with `with()`:

```php
// ✅ GOOD — 2 queries total, regardless of chef count
$chefs = Chef::with('recipes')->get();

foreach ($chefs as $chef) {
    // No additional query — recipes already loaded
    echo $chef->recipes->count();
}
```

You can also eager-load with constraints:

```php
// Load chefs with only their hard recipes
$chefs = Chef::with(['recipes' => function ($query) {
    $query->where('difficulty', 'hard')->orderBy('prep_time');
}])->get();
```

---

## Step 7 — Creating Related Records

There are several ways to create recipes through a chef's relationship:

```php
$chef = Chef::find(1);

// Method 1: create() — auto-injects chef_id
$chef->recipes()->create([
    'title'       => 'Panna Cotta',
    'description' => 'Silky Italian dessert',
    'prep_time'   => 30,
    'difficulty'  => 'easy',
]);

// Method 2: save() — attach an existing Recipe instance
$recipe = new Recipe([
    'title'       => 'Risotto al Funghi',
    'description' => 'Creamy mushroom risotto',
    'prep_time'   => 40,
    'difficulty'  => 'medium',
]);
$chef->recipes()->save($recipe);

// Method 3: saveMany() — attach multiple instances at once
$chef->recipes()->saveMany([$recipe1, $recipe2, $recipe3]);
```

---

## Step 8 — Counting with withCount

When you want to display how many recipes each chef has *without* loading them all:

```php
$chefs = Chef::withCount('recipes')->get();

foreach ($chefs as $chef) {
    // Adds a `recipes_count` attribute automatically
    echo "{$chef->name} has {$chef->recipes_count} recipes.\n";
}
```

You can even add conditions to the count:

```php
$chefs = Chef::withCount([
    'recipes',
    'recipes as hard_recipes_count' => fn ($q) => $q->where('difficulty', 'hard'),
])->get();

// Sofia Ricci: 8 total, 2 hard
```

---

## Step 9 — Aggregates on Related Models

Laravel also supports aggregate methods directly on relationships:

```php
$chef = Chef::find(1);

// Average prep time across all recipes
$avgPrepTime = $chef->recipes()->avg('prep_time');

// Total prep time
$totalPrep = $chef->recipes()->sum('prep_time');

// Shortest recipe
$quickest = $chef->recipes()->min('prep_time');
```

Or load aggregates alongside your models using `withAvg`, `withSum`, `withMin`, `withMax`:

```php
$chefs = Chef::withAvg('recipes', 'prep_time')
             ->withCount('recipes')
             ->get();

foreach ($chefs as $chef) {
    echo "{$chef->name}: avg prep time = {$chef->recipes_avg_prep_time} min\n";
}
```

---

## Step 10 — Filtering by Relationship: whereHas

Sometimes you need to filter *parents* based on properties of their *children*:

```php
// Chefs who have at least one hard recipe
$chefs = Chef::whereHas('recipes', function ($query) {
    $query->where('difficulty', 'hard');
})->get();

// Chefs who have MORE than 5 recipes
$prolificChefs = Chef::whereHas('recipes', null, '>', 5)->get();
// or
$prolificChefs = Chef::has('recipes', '>', 5)->get();

// Chefs who have NO recipes yet
$newChefs = Chef::doesntHave('recipes')->get();
```

---

## Step 11 — Deleting Related Records

When you delete a chef, you might want to remove their recipes. You have two options:

**Option 1: Database-level cascade** (set up in migration — already covered above):

```php
$table->foreignId('chef_id')->constrained()->onDelete('cascade');
```

This is the fastest approach — the database handles deletion.

**Option 2: Eloquent-level delete** (useful when you need to trigger model events/observers):

```php
$chef = Chef::find(1);

// Delete all recipes first, then the chef
$chef->recipes()->delete();
$chef->delete();
```

---

## Real-World Controller Example

Putting it all together in a typical resource controller:

```php
// app/Http/Controllers/ChefController.php

public function show(Chef $chef)
{
    $chef->load(['recipes' => function ($query) {
        $query->orderBy('difficulty')->orderBy('prep_time');
    }]);

    return view('chefs.show', compact('chef'));
}

public function index()
{
    $chefs = Chef::withCount('recipes')
                 ->withAvg('recipes', 'prep_time')
                 ->orderByDesc('recipes_count')
                 ->paginate(10);

    return view('chefs.index', compact('chefs'));
}
```

And in the Blade view:

```blade
{{-- resources/views/chefs/show.blade.php --}}

<h1>{{ $chef->name }}</h1>
<p>Specialty: {{ $chef->specialty }}</p>
<p>{{ $chef->recipes->count() }} recipe(s) published</p>

@forelse ($chef->recipes as $recipe)
    <div class="recipe-card">
        <h3>{{ $recipe->title }}</h3>
        <p>{{ $recipe->description }}</p>
        <span class="badge">{{ $recipe->difficulty }}</span>
        <span>{{ $recipe->prep_time }} min</span>
    </div>
@empty
    <p>This chef hasn't published any recipes yet.</p>
@endforelse
```

---

## Quick Reference

| Goal | Code |
|------|------|
| Define relationship | `$this->hasMany(Recipe::class)` |
| Get all related records | `$chef->recipes` |
| Query related records | `$chef->recipes()->where(...)` |
| Eager load | `Chef::with('recipes')->get()` |
| Create related record | `$chef->recipes()->create([...])` |
| Count related records | `$chef->recipes()->count()` |
| Count in query | `Chef::withCount('recipes')->get()` |
| Filter by child | `Chef::whereHas('recipes', fn($q) => ...)` |
| Cascade delete | `onDelete('cascade')` in migration |

---

## Summary

`hasMany` is one of the most frequently used Eloquent relationships, and for good reason — one-to-many structures appear everywhere in real applications. Here's what to take away:

- **Define it on the parent** model using `$this->hasMany(ChildModel::class)`
- **Access it as a property** (`$chef->recipes`) for a Collection, or **as a method** (`$chef->recipes()`) for a query builder
- **Always eager load** with `with()` when looping through parents to avoid the N+1 problem
- **Use `withCount`, `withAvg`, `withSum`** for efficient aggregation without loading full collections
- **Use `whereHas`** to filter parent records based on child conditions

The Chef/Recipe example here is simple, but the same patterns apply whether you're building e-commerce (orders → items), social apps (users → posts), or SaaS platforms (organizations → projects). Master `hasMany`, and you've mastered one of the most common data patterns in any application.
