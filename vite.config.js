// laravel-quotes-package/vite.config.js
import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export default defineConfig({
  plugins: [
    laravel({
      input: [
        "resources/js/app.js",
        "resources/css/app.css",
      ],
      publicDirectory: "public",
      buildDirectory: "vendor/laravel-quotes-package",
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  // resolve: {
  //     alias: {
  //         '@': path.resolve(__dirname, './resources/js'),
  //     }
  // },
  build: {
    outDir: "public/vendor/laravel-quotes-package",
    manifest: "manifest.json",
    emptyOutDir: true,
    rollupOptions: {
      input: {
        app: path.resolve(__dirname, "resources/js/app.js"),
        style: path.resolve(__dirname, "resources/css/app.css"),
      },
    },
  },
  // server: {
  //     origin: 'http://localhost:5173',
  // }
});