<?php

use App\Http\Controllers\WebCrawlerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    $maxPages = env('MAX_PAGES', 6);
    return view('home', compact('maxPages'));
});

Route::post('/crawl', [WebCrawlerController::class,'handleFormSubmit']);
