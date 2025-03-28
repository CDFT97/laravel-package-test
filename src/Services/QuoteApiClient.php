<?php

namespace Cdft\QuotesPackage\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Log;
use Exception;

class QuoteApiClient
{
  protected PendingRequest $httpClient;
  protected string $baseUrl;
  protected int $maxRequests;
  protected int $windowSeconds;

  // Estado del Rate Limiter (simple, en memoria)
  protected int $requestCount = 0;
  protected ?int $windowStartTime = null;

  // Caché local (simple, en memoria, array de arrays)
  protected array $localCache = [];
  protected bool $cacheNeedsSort = false; // Flag para indicar si hay que reordenar

  public function __construct()
  {
    $this->baseUrl = config('quotes.api.base_url', 'https://dummyjson.com');
    $this->maxRequests = config('quotes.rate_limit.max_requests', 10);
    $this->windowSeconds = config('quotes.rate_limit.window_seconds', 60);

    $this->httpClient = Http::baseUrl($this->baseUrl)->acceptJson();
  }

  /**
   * Fetches all quotes.
   * @return array|null
   * @throws Exception
   */
  public function getAllQuotes(): ?array
  {
    return $this->makeRequest('/quotes');
  }

  /**
   * Fetches a single random quote.
   * @return array|null
   * @throws Exception
   */
  public function getRandomQuote(): ?array
  {
    return $this->makeRequest('/quotes/random');
  }

  /**
   * Fetches a specific quote by ID, checking cache first using binary search.
   * @param int $id
   * @return array|null
   * @throws Exception
   */
  public function getQuote(int $id): ?array
  {
    // 1. Comprobar caché con búsqueda binaria
    $cachedQuote = $this->findInCache($id);
    if ($cachedQuote) {
      Log::debug("Quote ID {$id} found in local cache.");
      return $cachedQuote;
    }

    // 2. Si no está en caché, hacer petición API
    Log::debug("Quote ID {$id} not in cache. Fetching from API.");
    $quoteData = $this->makeRequest("/quotes/{$id}");

    // 3. Añadir a la caché si la petición fue exitosa y tiene ID
    if ($quoteData && isset($quoteData['id'])) {
      $this->addToCache($quoteData);
    }

    return $quoteData;
  }

  /**
   * Makes the actual HTTP request after checking rate limits.
   * @param string $endpoint
   * @return array|null
   * @throws Exception
   */
  protected function makeRequest(string $endpoint): ?array
  {
    $this->checkRateLimit();

    try {
      $response = $this->httpClient->get($endpoint);

      if ($response->successful()) {
        return $response->json();
      }

      Log::error("API Request Failed: {$endpoint}", [
        'status' => $response->status(),
        'body' => $response->body(),
      ]);
      return null;
    } catch (Exception $e) {
      Log::error("API Request Exception: {$endpoint}", ['exception' => $e->getMessage()]);
      throw $e;
    }
  }

  /**
   * Simple in-memory rate limiting check.
   */
  protected function checkRateLimit(): void
  {
    $now = time();

    // Resetear si la ventana ha expirado
    if ($this->windowStartTime === null || ($now > $this->windowStartTime + $this->windowSeconds)) {
      Log::debug("Rate limit window reset.");
      $this->requestCount = 0;
      $this->windowStartTime = $now;
    }

    // Comprobar si se ha alcanzado el límite
    if ($this->requestCount >= $this->maxRequests) {
      $sleepFor = ($this->windowStartTime + $this->windowSeconds) - $now;
      if ($sleepFor > 0) {
        Log::warning("Rate limit exceeded. Sleeping for {$sleepFor} seconds.");
        sleep($sleepFor); // Pausar ejecución

        // Resetear después de esperar
        $this->requestCount = 0;
        $this->windowStartTime = time(); // Nueva ventana empieza ahora
      }
    }

    // Incrementar contador para esta petición
    $this->requestCount++;
    Log::debug("Request count: {$this->requestCount}/{$this->maxRequests}");
  }

  // --- Cache Local Methods ---

  /**
   * Adds a quote to the local cache and marks for sorting.
   * @param array $quoteData Assumed to have an 'id' key.
   */
  protected function addToCache(array $quoteData): void
  {
    // Evitar duplicados
    if ($this->findInCache($quoteData['id']) === null) {
      $this->localCache[] = $quoteData;
      $this->cacheNeedsSort = true; // Marcar para ordenar antes de la próxima búsqueda
      Log::debug("Quote ID {$quoteData['id']} added to cache. Cache size: " . count($this->localCache));
    }
  }

  /**
   * Finds a quote in the local cache using binary search.
   * Ensures the cache is sorted before searching if needed.
   *
   * @param int $id
   * @return array|null The quote data if found, null otherwise.
   */
  protected function findInCache(int $id): ?array
  {
    if (empty($this->localCache)) {
      return null;
    }

    // Ordenar la caché por ID si es necesario antes de buscar
    $this->sortCacheIfNeeded();

    // Implementación de Búsqueda Binaria
    $low = 0;
    $high = count($this->localCache) - 1;

    while ($low <= $high) {
      $mid = (int) floor(($low + $high) / 2);
      $midId = $this->localCache[$mid]['id'] ?? null;

      if ($midId === null) { // Manejo de datos inesperados
        $high = $mid - 1; // Asumir que no está ordenado correctamente
        $this->cacheNeedsSort = true; // Forzar reordenación la próxima vez
        continue;
      }

      if ($midId == $id) {
        return $this->localCache[$mid]; // Encontrado
      } elseif ($midId < $id) {
        $low = $mid + 1; // Buscar en la mitad derecha
      } else {
        $high = $mid - 1; // Buscar en la mitad izquierda
      }
    }

    return null; // No encontrado
  }

  /**
   * Sorts the local cache by 'id' if the flag `cacheNeedsSort` is true.
   */
  protected function sortCacheIfNeeded(): void
  {
    if ($this->cacheNeedsSort && !empty($this->localCache)) {
      Log::debug("Sorting local cache by ID.");
      usort($this->localCache, function ($a, $b) {
        return ($a['id'] ?? 0) <=> ($b['id'] ?? 0); 
      });
      $this->cacheNeedsSort = false; // Marcar como ordenado
    }
  }
}
