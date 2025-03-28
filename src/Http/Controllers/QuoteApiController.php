<?php

namespace Cdft\QuotesPackage\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Cdft\QuotesPackage\Services\QuoteApiClient;
use Exception;

class QuoteApiController extends Controller
{
  protected QuoteApiClient $client;

  public function __construct(QuoteApiClient $client)
  {
    $this->client = $client;
  }

  /**
   * Get all quotes.
   */
  public function index(): JsonResponse
  {
    try {
      $quotes = $this->client->getAllQuotes();
      return response()->json($quotes ?: []);
    } catch (Exception $e) {
      return response()->json(['error' => 'Failed to fetch quotes', 'message' => $e->getMessage()], 500);
    }
  }

  /**
   * Get a random quote.
   */
  public function random(): JsonResponse
  {
    try {
      $quote = $this->client->getRandomQuote();
      return response()->json($quote ?: []);
    } catch (Exception $e) {
      return response()->json(['error' => 'Failed to fetch random quote', 'message' => $e->getMessage()], 500);
    }
  }

  /**
   * Get a specific quote by ID.
   */
  public function show(int $id): JsonResponse
  {
    try {
      $quote = $this->client->getQuote($id);
      if ($quote) {
        return response()->json($quote);
      } else {
        return response()->json(['error' => 'Quote not found'], 404);
      }
    } catch (Exception $e) {
      // Distinguir si el error fue un 404 real de la API o un error interno
      if (method_exists($e, 'getCode') && $e->getCode() == 404) {
        return response()->json(['error' => 'Quote not found'], 404);
      }
      return response()->json(['error' => 'Failed to fetch quote', 'message' => $e->getMessage()], 500);
    }
  }
}
