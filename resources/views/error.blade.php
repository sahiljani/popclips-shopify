<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error - Popclips</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            background: #f4f6f8;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        h1 { color: #d82c0d; margin-bottom: 16px; }
        p { color: #6d7175; margin-bottom: 24px; }
        a {
            display: inline-block;
            padding: 12px 24px;
            background: #008060;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover { background: #006e52; }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Something went wrong</h1>
        <p>{{ session('error', 'An unexpected error occurred. Please try again.') }}</p>
        <a href="{{ route('shopify.install') }}?shop={{ request('shop') }}">Try Again</a>
    </div>
</body>
</html>
