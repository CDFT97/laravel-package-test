<?php

namespace Cdft\QuotesPackage\Tests\Unit;

use Cdft\QuotesPackage\Services\QuoteApiClient;
use Cdft\QuotesPackage\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Mockery;

class QuoteApiClientTest extends TestCase
{
  // Limpiar mocks de Mockery después de cada test
  public function tearDown(): void
  {
    Carbon::setTestNow(); // Asegurarse de resetear el tiempo de Carbon
    Mockery::close();
    parent::tearDown();
  }

  public function test_get_all_quotes_makes_correct_api_call()
  {
    Http::fake([
      'dummyjson.com/quotes' => Http::response(['quotes' => [['id' => 1, 'quote' => 'Test Quote', 'author' => 'Tester']]], 200),
    ]);

    // Asegurarse de que la URL base de la config de test se usa
    config(['quotes.api.base_url' => 'https://dummyjson.com']);
    $client = $this->app->make(QuoteApiClient::class);
    $result = $client->getAllQuotes();

    Http::assertSent(function ($request) {
      // Verificar la URL completa incluyendo el host configurado
      return $request->url() === config('quotes.api.base_url') . '/quotes'
        && $request->method() === 'GET';
    });
    $this->assertIsArray($result);
    $this->assertArrayHasKey('quotes', $result);
  }

  public function test_get_random_quote_makes_correct_api_call()
  {
    Http::fake([
      'dummyjson.com/quotes/random' => Http::response(['id' => 5, 'quote' => 'Random Quote', 'author' => 'Randomizer'], 200),
    ]);

    config(['quotes.api.base_url' => 'https://dummyjson.com']);
    $client = $this->app->make(QuoteApiClient::class);
    $result = $client->getRandomQuote();

    Http::assertSent(function ($request) {
      return $request->url() === config('quotes.api.base_url') . '/quotes/random'
        && $request->method() === 'GET';
    });
    $this->assertIsArray($result);
    $this->assertEquals(5, $result['id']);
  }

  public function test_get_quote_by_id_fetches_from_api_and_caches()
  {
    $quoteId = 10;
    $quoteData = ['id' => $quoteId, 'quote' => 'Specific Quote', 'author' => 'Specific Author'];
    $baseUrl = config('quotes.api.base_url', 'https://dummyjson.com'); // Usar config para la URL

    // Fake para la llamada específica y un comodín para otras
    Http::fake([
      "{$baseUrl}/quotes/{$quoteId}" => Http::response($quoteData, 200),
      "{$baseUrl}/*" => Http::response([], 404),
    ]);

    $client = $this->app->make(QuoteApiClient::class);

    // 1. Primera llamada (debe ir a la API)
    $result1 = $client->getQuote($quoteId);

    // Verificar que la llamada se hizo a la URL correcta
    Http::assertSent(function ($request) use ($quoteId, $baseUrl) {
      return $request->url() === "{$baseUrl}/quotes/{$quoteId}";
    });
    $this->assertEquals($quoteData, $result1);

    // 2. Segunda llamada (debe venir de la caché)

    $result2 = $client->getQuote($quoteId);

    // Verificar que SOLO se hizo UNA llamada HTTP en total para esta ID
    Http::assertSentCount(1);
    $this->assertEquals($quoteData, $result2); // Verificar que el resultado cacheado es correcto
  }

  public function get_quote_by_id_uses_cache_after_first_fetch() 
  {
    // Crear cliente y datos de prueba
    $client = $this->app->make(QuoteApiClient::class);
    $quote5 = ['id' => 5, 'quote' => 'Q5', 'author' => 'A5'];
    $quote2 = ['id' => 2, 'quote' => 'Q2', 'author' => 'A2'];
    $quote8 = ['id' => 8, 'quote' => 'Q8', 'author' => 'A8'];
    $baseUrl = config('quotes.api.base_url', 'https://dummyjson.com');

    // Configurar respuestas fake para llamadas iniciales usando un callback para mayor claridad
    Http::fake(function (\Illuminate\Http\Client\Request $request) use ($baseUrl, $quote2, $quote5, $quote8) {
      if ($request->url() === "{$baseUrl}/quotes/2") {
        return Http::response($quote2, 200); // Devolver datos falsos para ID 2
      }
      if ($request->url() === "{$baseUrl}/quotes/5") {
        return Http::response($quote5, 200);
      }
      if ($request->url() === "{$baseUrl}/quotes/8") {
        return Http::response($quote8, 200);
      }
      // Fallback explícito para cualquier otra llamada inesperada
      return Http::response(['error' => 'Unexpected API call in test: ' . $request->url()], 500);
    });

    // Act 1: Llamadas para poblar la caché (desordenadas)
    $client->getQuote(5);
    $firstResultFor2 = $client->getQuote(2); // Primera llamada para ID 2
    $client->getQuote(8);

    // Assert 1: Verificar que la PRIMERA llamada a getQuote(2) devolvió los datos FALSOS
    $this->assertEquals($quote2, $firstResultFor2, "First call to getQuote(2) did not return the expected fake data.");
    // Verificar que se hicieron las 3 llamadas iniciales
    Http::assertSentCount(3);

    // Act 2: Segunda llamada para ID 2 (debería usar caché)
    $secondResultFor2 = $client->getQuote(2);

    // Assert 2: Verificar que NO hubo llamadas API ADICIONALES y el resultado es el esperado (cacheado)
    Http::assertSentCount(3); // El contador total NO debe haber aumentado
    $this->assertEquals($quote2, $secondResultFor2, "Second call to getQuote(2) did not return the cached fake data.");

    // Act/Assert 3: Probar otro elemento cacheado (ID 8)
    $secondResultFor8 = $client->getQuote(8);
    Http::assertSentCount(3); // El contador total sigue siendo 3
    $this->assertEquals($quote8, $secondResultFor8, "Second call to getQuote(8) did not return the cached fake data.");
  }

  /**
   * @test
   * Testea que el rate limiting pausa la ejecución (mediante los logs)
   * cuando se excede el límite de peticiones.
   */
  public function test_rate_limit_pauses_execution_and_logs_warning()
  {
    // 1. Configurar límites bajos y tiempo controlado
    $maxRequests = 2;
    $windowSeconds = 10;
    config([
      'quotes.rate_limit.max_requests' => $maxRequests,
      'quotes.rate_limit.window_seconds' => $windowSeconds,
      'quotes.api.base_url' => 'https://dummyjson.com',
    ]);
    $baseUrl = config('quotes.api.base_url');

    // Preparar respuesta fake de la API
    Http::fake(["{$baseUrl}/*" => Http::response(['id' => 1, 'quote' => 'Ok'], 200)]);

    Log::spy(); // inicializa el spy para el Facade de Log

    // Obtener instancia del cliente
    $client = $this->app->make(QuoteApiClient::class);

    // Fijar el tiempo inicial
    $startTime = Carbon::create(2024, 1, 1, 12, 0, 0);
    Carbon::setTestNow($startTime);

    // 2. Act: Realizar peticiones
    $client->getRandomQuote(); // Petición 1
    $client->getRandomQuote(); // Petición 2

    // Avanzar el tiempo ligeramente
    Carbon::setTestNow($startTime->copy()->addSeconds(1));

    // Petición 3 (debería disparar el Log::warning internamente)
    $client->getRandomQuote();

    // Verificar que Log::warning FUE llamado con los argumentos correctos
    $expectedSleepDuration = $windowSeconds - 1;
    Log::shouldHaveReceived('warning') // Verifica llamadas al método 'warning'
      ->once() // Exactamente una vez
      ->with(Mockery::on(function ($message) use ($expectedSleepDuration) { // Con un mensaje que cumpla la condición
        return str_contains($message, 'Rate limit exceeded')
          && str_contains($message, "Sleeping for {$expectedSleepDuration} seconds");
      }));

    // Avanzar tiempo para la siguiente ventana
    Carbon::setTestNow($startTime->copy()->addSeconds($windowSeconds + 1));

    // Petición 4
    $client->getRandomQuote();

    // 3. Assert:
    Http::assertSentCount(4); // Verificar número total de llamadas HTTP

    // Resetear Carbon
    Carbon::setTestNow();
  }
}
