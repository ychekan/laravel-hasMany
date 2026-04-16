<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $chef->name }} — Cooking Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <style>
        body {
            font-family: sans-serif;
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .recipe-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-easy {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-medium {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-hard {
            background: #fee2e2;
            color: #991b1b;
        }

        a {
            color: #2563eb;
        }

        .custom-mt {
            margin-top:0.5rem;
        }
    </style>
</head>
<body>

<p><a href="{{ route('chefs.index') }}">&larr; Back to all chefs</a></p>

<h1>{{ $chef->name }}</h1>
<p><strong>Specialty:</strong> {{ $chef->specialty }}</p>
<p>{{ $chef->recipes->count() }} recipe(s) published</p>

@if (session('success'))
    <p class="text-success">{{ session('success') }}</p>
@endif

<h2>Recipes</h2>

@forelse ($chef->recipes as $recipe)
    <div class="recipe-card">
        <h3>{{ $recipe->title }}</h3>
        <p>{{ $recipe->description }}</p>
        <span class="badge badge-{{ $recipe->difficulty }}">{{ ucfirst($recipe->difficulty) }}</span>
        <span class="ms-4">⏱ {{ $recipe->prep_time }} min</span>
    </div>
@empty
    <p>This chef hasn't published any recipes yet.</p>
@endforelse
<hr />
<h2 class="mt-4">Add a Recipe</h2>
<form method="POST" action="{{ route('chefs.recipes.store', $chef) }}">
    @csrf
    <div class="custom-mt mb-3">
        <label class="form-label w-100">Title<br>
            <input type="text" class="form-control" name="title" value="{{ old('title') }}" required>
        </label>
        @error('title') <p class="text-danger">{{ $message }}</p> @enderror
    </div>
    <div class="custom-mt mb-3">
        <label class="form-label w-100">Description<br>
            <textarea name="description" class="form-control" rows="3" required>{{ old('description') }}</textarea>
        </label>
        @error('description') <p class="text-danger">{{ $message }}</p> @enderror
    </div>
    <div class="custom-mt mb-3">
        <label class="form-label w-100">Prep time (minutes)<br>
            <input type="number" class="form-control" name="prep_time" value="{{ old('prep_time') }}" min="1" required>
        </label>
        @error('prep_time') <p class="text-danger">{{ $message }}</p> @enderror
    </div>
    <div class="custom-mt mb-3">
        <label class="form-label w-100">Difficulty<br>
            <select name="difficulty" class="form-select" required>
                <option value="">— choose —</option>
                @foreach (['easy', 'medium', 'hard'] as $level)
                    <option
                        value="{{ $level }}" {{ old('difficulty') === $level ? 'selected' : '' }}>{{ ucfirst($level) }}</option>
                @endforeach
            </select>
        </label>
        @error('difficulty') <p class="text-danger">{{ $message }}</p> @enderror
    </div>
    <button type="submit" class="btn btn-primary">Add Recipe</button>
</form>
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4"
        crossorigin="anonymous"></script>
</body>
</html>

