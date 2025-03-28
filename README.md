# Laravel Quotes Package (Cdft/laravel-quotes-package) made by [Cesar Febres](https://github.com/CDFT97)

A Laravel package that interacts with the [dummyjson.com/quotes](https://dummyjson.com/quotes) API to fetch quotes. It includes features like configurable rate limiting for the external API, local caching with binary search for individual quotes, and a pre-built user interface using Vue.js 3 and Tailwind CSS.

## Features

- Interacts with the `https://dummyjson.com/quotes` API.
- Methods in the `QuoteApiClient` service:
  - `getAllQuotes()`: Fetches all quotes.
  - `getRandomQuote()`: Fetches a single random quote.
  - `getQuote(id)`: Fetches a specific quote by its ID (with caching and binary search).
- **Rate Limiting:** Configurable control over the number of requests made to the external API within a time window. Includes automatic pausing if the limit is exceeded.
- **Local Caching:** In-memory storage for quotes fetched via `getQuote(id)`, with efficient retrieval using binary search.
- **API Endpoints:** Provides ready-to-use API routes:
  - `GET /api/quotes`: Returns all quotes.
  - `GET /api/quotes/random`: Returns a random quote.
  - `GET /api/quotes/{id}`: Returns a specific quote by ID.
- **Vue.js User Interface (UI):** Includes a pre-built UI using Vue 3 and Tailwind CSS, served at the `/quotes-ui` route. Allows viewing all quotes (with pagination), a random quote, or searching by ID.
- **Publishable Assets:** Allows publishing the configuration file, views (Blade), and compiled frontend assets (JS/CSS).
- **Tests:** Includes Unit tests (for the API service) and Feature tests (for the API routes).

## Requirements

- PHP ^8.1
- Laravel ^9.0 || ^10.0 || ^11.0 (Ensure your host application uses one of these versions)
- Composer
- Git (for cloning the package)
- Node.js and npm (only if you want to modify and rebuild the Vue.js UI)

## Installation (Local Development / Manual Setup)

Since this package might not be available on Packagist, follow these steps to install it locally within your Laravel project:

1.  **Clone or Download the Package:**
    Obtain the package source code. If you have it in a Git repository, clone it to a location _outside_ your main Laravel application's directory. For example, if your Laravel app is in `C:\laragon\www\my-laravel-app`, you could clone the package to `C:\laragon\www\laravel-quotes-package`:

    ```bash
    # Navigate to the desired parent directory (e.g., C:\laragon\www\)
    cd /path/to/where/you/want/the/package/folder

    # Clone the repository (replace with your actual repo URL if applicable)
    git clone <your-package-repository-url> laravel-quotes-package
    # Or, if you already have the folder, just make sure it's in a known location.
    ```

2.  **Configure Host Application's `composer.json`:**
    Open the `composer.json` file located in the **root directory of your main Laravel application** (e.g., `C:\laragon\www\my-laravel-app\composer.json`).

    - **Add a `repositories` section:** This tells Composer where to look for the package locally.
    - **Add the package to the `require` section.**

    Modify your application's `composer.json` like this:

    ```json
    {
      // ... other sections (name, description, etc.) ...

      "require": {
        // ... other Laravel dependencies ...
        "php": "^8.1", // Or your app's PHP requirement
        "laravel/framework": "^11.0", // Or your app's framework version

        "cdft/laravel-quotes-package": "*" // Or "dev-main" if using Git
      },

      // ... require-dev, autoload, etc. ...

      "repositories": [
        {
          "type": "path",
          // IMPORTANT: Adjust the URL to the correct RELATIVE path
          // from your Laravel application root to the package directory.
          "url": "../laravel-quotes-package"
        }
        // You might have other repositories here
      ],

      // Ensure minimum-stability allows dev versions if needed
      "minimum-stability": "dev",
      "prefer-stable": true
    }
    ```

    - **Crucial:** Update the `"url"` value in the `repositories` section to the correct relative path from your Laravel application to the `laravel-quotes-package` directory. If they are side-by-side in `www`, `../laravel-quotes-package` is usually correct.

3.  **Install via Composer:**
    Navigate to the **root directory of your main Laravel application** in your terminal and run:

    ```bash
    composer update cdft/laravel-quotes-package --prefer-source
    ```

    Or simply:

    ```bash
    composer update
    ```

    Composer will now find your local package via the `path` repository and create a symbolic link (or copy) into your application's `vendor` directory.

4.  **Verify:**
    The package's Service Provider should be automatically registered via discovery. You can optionally verify routes are loaded:
    ```bash
    php artisan route:list | findstr quotes # Windows
    # php artisan route:list | grep quotes # Linux/Mac
    ```

Now the package is installed and linked for local use within your Laravel application. Remember to follow the steps in the "Vue.js User Interface (UI)" section below to publish the frontend assets.

## Configuration

Although the package works with default settings, you can publish and modify its configuration file to customize behavior:

```bash
php artisan vendor:publish --provider="Cdft\QuotesPackage\Providers\QuotesServiceProvider" --tag="config"
```

## Access the UI:

Once the assets are published, simply navigate to the following route in your browser:

/quotes-ui

(For example: http://your-application.test/quotes-ui)

-------------------------------

The package includes automated tests. To run them, ensure you have Composer's development dependencies installed (composer install --dev inside the package directory) and run PHPUnit from the package's root directory:

# Navigate to the package directory
# Example: cd C:\www\laravel-quotes-package
cd /path/to/laravel-quotes-package

# Run tests using the vendor executable
./vendor/bin/phpunit
# Or on Windows: vendor\bin\phpunit