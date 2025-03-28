<?php

namespace Cdft\QuotesPackage\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Cdft\QuotesPackage\Providers\QuotesServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
  
  protected function getPackageProviders($app)
  {
    return [
      QuotesServiceProvider::class,
    ];
  }

  protected function defineEnvironment($app)
  {
    // Opcional -  Definir config específica para tests si es necesario
    // $app['config']->set('database.default', 'testing');
    $app['config']->set('quotes.api.base_url', 'https://dummyjson.com'); // Usa la URL real o una falsa
    $app['config']->set('quotes.rate_limit.max_requests', 50); // Aumentar para tests
    $app['config']->set('quotes.rate_limit.window_seconds', 10);
    // Genera una clave válida sobre la marcha para el entorno de prueba
    $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
  }

  /**
   * Define routes setup.
   *
   * @param \Illuminate\Routing\Router $router
   * @return void
   */
  protected function defineRoutes($router)
  {
    // Si por algun motivo las rutas no se cargan automaticamente en testbench
    // require __DIR__.'/../routes/api.php';
    // require __DIR__.'/../routes/web.php';
  }
}
