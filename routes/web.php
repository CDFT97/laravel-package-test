<?php

use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
  Route::get('/quotes-ui', function () {
    return view('quotes-package::ui');
  })->name('quotes-package.ui');
});
