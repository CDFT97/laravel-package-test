<?php
// config/quotes.php

return [
  /*
    |--------------------------------------------------------------------------
    | DummyJSON API Configuration
    |--------------------------------------------------------------------------
    */
  'api' => [
    'base_url' => env('QUOTES_API_BASE_URL', 'https://dummyjson.com'),
  ],

  /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | Define the maximum number of requests allowed within a specific
    | time window (in seconds) when interacting with the API.
    |
    */
  'rate_limit' => [
    'max_requests' => env('QUOTES_RATE_LIMIT_MAX', 10), // Ejemplo: 10 peticiones
    'window_seconds' => env('QUOTES_RATE_LIMIT_WINDOW', 60), // Ejemplo: por minuto (60 segundos)
  ],

  /*
     |--------------------------------------------------------------------------
     | Caching Configuration
     |--------------------------------------------------------------------------
     | Note: For more robust caching, consider integrating Laravel's Cache facade.
     */
  // 'cache_driver' => env('QUOTES_CACHE_DRIVER', 'array'), // how to use if you want to cache the quotes with another driver
];
