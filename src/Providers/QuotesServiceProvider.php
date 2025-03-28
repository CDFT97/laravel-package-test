<?php

namespace Cdft\QuotesPackage\Providers;

use Illuminate\Support\ServiceProvider;
use Cdft\QuotesPackage\Services\QuoteApiClient;

class QuotesServiceProvider extends ServiceProvider
{
  public function register(): void
  {
    // Fusionar config del paquete con la config de la app principal
    $this->mergeConfigFrom(
      __DIR__ . '/../../config/quotes.php',
      'quotes'
    );

    // Registrar el servicio API Client en el contenedor de servicios (como singleton)
    $this->app->singleton(QuoteApiClient::class, function ($app) {
      return new QuoteApiClient();
    });
  }

  /**
   * Bootstrap services.
   */
  public function boot(): void
  {
    // Cargar rutas API
    $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
    // Cargar rutas Web (para la UI)
    $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');

    // Cargar vistas, especificando un namespace para evitar colisiones
    $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'quotes-package');

    // Definir assets publicables
    if ($this->app->runningInConsole()) {
      // Publicar archivo de configuración
      $this->publishes([
        __DIR__ . '/../../config/quotes.php' => config_path('quotes.php'),
      ], 'config'); // Tag 'config'

      // Publicar vistas
      $this->publishes([
        __DIR__ . '/../../resources/views' => resource_path('views/vendor/quotes-package'),
      ], 'views'); // Tag 'views'

      // Publicar los assets compilados de Vue/Vite
      // El origen es donde Vite construye los assets DENTRO del paquete
      // El destino es la carpeta public de la aplicación Laravel principal
      $this->publishes([
        __DIR__ . '/../../public/vendor/laravel-quotes-package' => public_path('vendor/laravel-quotes-package'),
      ], 'public');
    }
  }
}
