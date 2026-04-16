<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chefs — Cooking Platform</title>
    <link href="//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <style>
        body { font-family: sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.6rem 1rem; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<h1>Our Chefs</h1>

@if ($chefs->isEmpty())
    <p>No chefs found.</p>
@else
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Specialty</th>
                <th>Recipes</th>
                <th>Avg Prep Time</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($chefs as $chef)
                <tr>
                    <td>{{ $chef->id }}</td>
                    <td><a href="{{ route('chefs.show', $chef) }}">{{ $chef->name }}</a></td>
                    <td>{{ $chef->specialty }}</td>
                    <td>{{ $chef->recipes_count }}</td>
                    <td>{{ $chef->recipes_avg_prep_time !== null ? round($chef->recipes_avg_prep_time) . ' min' : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:1rem;">
        {{ $chefs->links() }}
    </div>
@endif
<script src="//cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
</body>
</html>

