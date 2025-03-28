<?php

namespace Cdft\QuotesPackage\Tests\Feature;

use Cdft\QuotesPackage\Tests\TestCase;
use Cdft\QuotesPackage\Services\QuoteApiClient; 
use Mockery; // Para mocks más avanzados

class ApiRoutesTest extends TestCase
{
  // Mockear el servicio para no depender de la API real en tests de rutas
  public function tearDown(): void
  {
    Mockery::close();
    parent::tearDown();
  }

  protected function mockApiClient()
  {
    $mock = Mockery::mock(QuoteApiClient::class);
    $this->app->instance(QuoteApiClient::class, $mock); // Sobrescribir la instancia en el contenedor
    return $mock;
  }

  public function test_get_all_quotes_route_returns_data()
  {
    $mock = $this->mockApiClient();
    $mock->shouldReceive('getAllQuotes')
      ->once()
      ->andReturn(['quotes' => [['id' => 1, 'quote' => 'Q', 'author' => 'A']], 'total' => 1, /* ... */]);

    $response = $this->getJson('/api/quotes');

    $response->assertStatus(200)
      ->assertJsonStructure([ 
        'quotes' => [
          '*' => ['id', 'quote', 'author']
        ],
      ])
      ->assertJsonFragment(['id' => 1]);
  }

  public function test_get_random_quote_route_returns_data()
  {
    $mock = $this->mockApiClient();
    $mock->shouldReceive('getRandomQuote')
      ->once()
      ->andReturn(['id' => 99, 'quote' => 'Random Q', 'author' => 'Random A']);

    $response = $this->getJson('/api/quotes/random');

    $response->assertStatus(200)
      ->assertJsonFragment(['id' => 99, 'quote' => 'Random Q']);
  }

  public function test_get_specific_quote_route_returns_data()
  {
    $quoteId = 15;
    $mock = $this->mockApiClient();
    $mock->shouldReceive('getQuote')
      ->with($quoteId) // Asegura que se llama con el ID correcto
      ->once()
      ->andReturn(['id' => $quoteId, 'quote' => 'Specific Q', 'author' => 'Specific A']);

    $response = $this->getJson("/api/quotes/{$quoteId}");

    $response->assertStatus(200)
      ->assertJson(['id' => $quoteId, 'quote' => 'Specific Q']);
  }

  public function test_get_specific_quote_route_returns_404_if_not_found()
  {
    $quoteId = 9999;
    $mock = $this->mockApiClient();
    $mock->shouldReceive('getQuote')
      ->with($quoteId)
      ->once()
      // Simular que el servicio devuelve null o lanza una excepción específica
      // que el controlador convierte en 404
      ->andReturn(null); // El controlador debería manejar esto como 404

    $response = $this->getJson("/api/quotes/{$quoteId}");

    $response->assertStatus(404)
      ->assertJson(['error' => 'Quote not found']);
  }
}
