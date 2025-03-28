/** @type {import('tailwindcss').Config} */
export default {
  content: [
    // Rutas DENTRO del paquete donde Tailwind buscará clases
    "./resources/views/**/*.blade.php", // Para el ui.blade.php
    "./resources/js/**/*.vue",        // Para los componentes Vue
  ],
  theme: {
    extend: {
    },
  },
  plugins: [
  ],
}