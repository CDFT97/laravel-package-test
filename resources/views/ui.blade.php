<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Quotes UI</title>

  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

  @vite(['resources/js/app.js', 'resources/css/app.css'], 'vendor/laravel-quotes-package')

</head>

<body class="font-sans antialiased bg-gray-100 p-5">
  <div id="app" class="bg-white p-6 rounded-lg shadow-md max-w-4xl mx-auto">
    <p>Loading Vue Application...</p>
  </div>
</body>

</html>