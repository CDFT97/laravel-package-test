<template>
  <div>
    <h1>Quotes Explorer</h1>

    <div>
      <button @click="fetchAllQuotes" :disabled="loading">Load All Quotes</button>
      <button @click="fetchRandomQuote" :disabled="loading">Get Random Quote</button>
      <div>
        <input type="number" v-model.number="quoteIdInput" placeholder="Enter Quote ID" />
        <button @click="fetchQuoteById" :disabled="loading || !quoteIdInput">Get Quote by ID</button>
      </div>
    </div>

    <div v-if="loading" class="loading">Loading...</div>
    <div v-if="error" class="error">Error: {{ error }}</div>

    <div v-if="quotes && quotes.length">
      <h2>{{ currentView === 'all' ? 'All Quotes' : (currentView === 'single' ? 'Specific Quote' : 'Random Quote') }}
      </h2>
      <!-- Muestra una lista si son todos, o el quote individual -->
      <div v-if="currentView === 'all'">
        <div v-for="quote in quotes" :key="quote.id" class="quote-item">
          <blockquote>"{{ quote.quote }}"</blockquote>
          <footer>- {{ quote.author }} (ID: {{ quote.id }})</footer>
        </div>

      </div>
      <div v-else-if="(currentView === 'single' || currentView === 'random') && quotes[0]">
        <div class="quote-item">
          <blockquote>"{{ quotes[0].quote }}"</blockquote>
          <footer>- {{ quotes[0].author }} (ID: {{ quotes[0].id }})</footer>
        </div>
      </div>
    </div>
    <div v-else-if="!loading && !error && currentView">
      <p>No quotes found.</p>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios'; // O usa fetch

const quotes = ref([]);
const loading = ref(false);
const error = ref(null);
const quoteIdInput = ref(null);
const currentView = ref(''); // 'all', 'single', 'random'

const API_BASE = '/api/quotes';

const fetchData = async (url) => {
  loading.value = true;
  error.value = null;
  quotes.value = []; // Limpiar antes de nueva petición
  try {
    const { data } = await axios.get(url);
    // Manejar la respuesta de getAllQuotes que tiene una estructura diferente
    if (url === API_BASE && data && Array.isArray(data.quotes)) {
      quotes.value = data.quotes;
    } else if (data && data.id) { // Para single y random
      quotes.value = [data]; // Poner en un array para consistencia
    } else if (url === API_BASE) {
      // Si getAllQuotes devuelve null o un formato inesperado
      quotes.value = [];
      console.warn("Received unexpected format for all quotes:", response.data);
    } else {
      // Si getById o random devuelve null/404
      quotes.value = [];
    }

  } catch (err) {
    console.error("API Error:", err);
    error.value = err.response?.data?.error || err.response?.statusText || err.message || 'Failed to fetch data';
    if (err.response?.status === 404) {
      error.value = 'Quote not found.'; // Mensaje específico para 404
    }
    quotes.value = [];
  } finally {
    loading.value = false;
  }
}

const fetchAllQuotes = () => {
  currentView.value = 'all';
  fetchData(API_BASE);
}

const fetchRandomQuote = () => {
  currentView.value = 'random';
  fetchData(`${API_BASE}/random`);
}

const fetchQuoteById = () => {
  if (!quoteIdInput.value) return;
  currentView.value = 'single';
  fetchData(`${API_BASE}/${quoteIdInput.value}`);
}
</script>

<style scoped>
input[type=number] {
  width: 150px;
}

div>div {
  margin-top: 15px;
}
</style>