# Laravel Web Crawler

A simple Laravel web crawler that crawls a given website and displays various metrics.

## Requirements

- PHP >= 8.1
- Composer

## Setup

Clone the repository:

```
git clone https://github.com/kenstuddy/laravel-web-crawler.git
```

Change the working directory:

```
cd laravel-web-crawler
```

Install dependencies using Composer:

```
composer install
```

Copy the `.env.example` file to create your own `.env` file:

```
cp .env.example .env
```

Generate an application key:

```
php artisan key:generate
```

Start the Laravel development server:

```
php artisan serve
```

Access the web crawler in your browser at the URL displayed in the console (usually http://127.0.0.1:8000).

## Usage

- Enter a website URL to start the crawl and display the results.

## Customization

- Modify the `WebCrawlerController` class to change the behavior of the web crawler.
- Edit the `results.blade.php` file to customize the appearance of the results page.
- Edit the `MAX_PAGES` variable in the `.env` file to set the maximum number of pages that can be crawled.
