<template>
  <div class="max-w-4xl mx-auto p-6">
    <h1 class="text-3xl font-bold text-gray-800 mb-8 text-center">Quotes Explorer</h1>

    <div
      class="flex flex-col sm:flex-row flex-wrap items-center justify-center gap-4 mb-8 p-4 bg-gray-100 rounded-lg shadow-sm">
      <button @click="fetchAllQuotes" :disabled="loading"
        class="bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 px-4 rounded shadow transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
        Load All Quotes
      </button>

      <button @click="fetchRandomQuote" :disabled="loading"
        class="bg-green-500 hover:bg-green-600 text-white font-semibold py-2 px-4 rounded shadow transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
        Get Random Quote
      </button>

      <div class="flex items-center mt-4 sm:mt-0">
        <input type="number" v-model.number="quoteIdInput" placeholder="Enter Quote ID"
          class="w-32 border border-gray-300 rounded-l py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-transparent" />
        <button @click="fetchQuoteById" :disabled="loading || !quoteIdInput"
          class="bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-2 px-4 rounded-r shadow transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed border border-indigo-500 -ml-px">
          Get by ID
        </button>
      </div>
    </div>

    <div v-if="loading" class="text-center text-gray-500 font-medium py-10">
      <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
        viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
        </path>
      </svg>
      Loading Quotes...
    </div>

    <div v-if="error" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md shadow-sm my-6"
      role="alert">
      <p class="font-bold">Error</p>
      <p>{{ error }}</p>
    </div>

    <div v-if="!loading && displayedQuotes && displayedQuotes.length" class="mt-8">
      <h2 class="text-2xl font-semibold text-gray-700 mb-5 text-center">
        {{ currentView === 'all' ? 'All Quotes' : (currentView === 'single' ? 'Specific Quote' : 'Random Quote') }}
      </h2>

      <div v-if="currentView === 'all'" class="space-y-4">
        <div v-for="quote in displayedQuotes" :key="quote.id"
          class="bg-white border border-gray-200 p-5 rounded-lg shadow-sm hover:shadow-md transition duration-150 ease-in-out">
          <blockquote class="text-lg italic text-gray-800 mb-3 border-l-4 border-blue-300 pl-4">
            "{{ quote.quote }}"
          </blockquote>
          <footer class="text-right text-sm text-gray-500 font-medium">
            - {{ quote.author }} <span class="text-gray-400">(ID: {{ quote.id }})</span>
          </footer>
        </div>

        <div v-if="totalPages > 1" class="flex justify-center items-center mt-8 space-x-4">
           <button @click="prevPage" :disabled="currentPage === 1"
             class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-l disabled:opacity-50 disabled:cursor-not-allowed">
            « Previous
          </button>
          <span class="text-gray-700 font-medium">
            Page {{ currentPage }} of {{ totalPages }}
          </span>
          <button @click="nextPage" :disabled="currentPage === totalPages"
             class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded-r disabled:opacity-50 disabled:cursor-not-allowed">
            Next »
          </button>
        </div>
      </div>

      <div v-else-if="(currentView === 'single' || currentView === 'random') && displayedQuotes[0]"
        class="bg-white border border-gray-200 p-6 rounded-lg shadow-md max-w-2xl mx-auto">
        <blockquote class="text-xl italic text-center text-gray-800 mb-4 border-l-4 border-green-300 pl-4 py-2">
          "{{ displayedQuotes[0].quote }}"
        </blockquote>
        <footer class="text-right text-base text-gray-600 font-medium mt-4">
          - {{ displayedQuotes[0].author }} <span class="text-gray-400">(ID: {{ displayedQuotes[0].id }})</span>
        </footer>
      </div>
    </div>

    <div v-else-if="!loading && !error && currentView && (!displayedQuotes || displayedQuotes.length === 0)"
      class="text-center text-gray-500 py-10">
      <p class="text-lg">No quotes found for the current selection.</p>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import axios from 'axios';

const allQuotes = ref([]);
const displayedQuotes = ref([]);
const loading = ref(false);
const error = ref(null);
const quoteIdInput = ref(null);
const currentView = ref('');

const itemsPerPage = ref(5);
const currentPage = ref(1);

const API_BASE = '/api/quotes';

const totalPages = computed(() => {
  return Math.ceil(allQuotes.value.length / itemsPerPage.value);
});

const updateDisplayedQuotes = () => {
  if (currentView.value !== 'all') return;

  const startIndex = (currentPage.value - 1) * itemsPerPage.value;
  const endIndex = startIndex + itemsPerPage.value;
  displayedQuotes.value = allQuotes.value.slice(startIndex, endIndex);
};

const fetchData = async (url) => {
  loading.value = true;
  error.value = null;
  displayedQuotes.value = [];
  allQuotes.value = [];
  currentPage.value = 1;

  try {
    const { data } = await axios.get(url);

    if (url === API_BASE && data && Array.isArray(data.quotes)) {
      allQuotes.value = data.quotes;
      updateDisplayedQuotes(); // Carga la primera página
    } else if (data && data.id) {
      displayedQuotes.value = [data]; // Muestra directamente la cita única
    } else {
      displayedQuotes.value = [];
    }

  } catch (err) {
    console.error("API Error:", err);
    error.value = err.response?.data?.error || err.response?.statusText || err.message || 'Failed to fetch data';
    if (err.response?.status === 404) {
      error.value = 'Quote not found.';
    }
    displayedQuotes.value = [];
    allQuotes.value = [];
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

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    updateDisplayedQuotes();
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    updateDisplayedQuotes();
  }
};

</script>
