<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f7f7fb; color: #1f2937; }
        .wrap { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 760px; width: 100%; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; text-align: center; }
        h1 { margin: 0 0 12px; font-size: 30px; }
        p { margin: 0; font-size: 16px; color: #4b5563; }
        img { margin-top: 20px; max-width: 100%; border-radius: 10px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>{{ $title ?? 'We will be back soon!' }}</h1>
        <p>{{ $subtitle ?? 'Sorry for the inconvenience but we are performing some maintenance at the moment.' }}</p>
        @if(!empty($imagePath))
            <img src="{{ asset($imagePath) }}" alt="Maintenance">
        @endif
    </div>
</div>
</body>
</html>

