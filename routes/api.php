<?php

use Illuminate\Support\Facades\Route;
use Cdft\QuotesPackage\Http\Controllers\QuoteApiController;

Route::middleware('api')->prefix('api/quotes')->group(function () {
  Route::get('/', [QuoteApiController::class, 'index']);
  Route::get('/random', [QuoteApiController::class, 'random']);
  Route::get('/{id}', [QuoteApiController::class, 'show'])->where('id', '[0-9]+');
});
