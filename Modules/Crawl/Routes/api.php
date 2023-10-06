<?php

use Illuminate\Support\Facades\Route;
use Modules\Crawl\Http\Controllers\CrawlController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/crawl', [CrawlController::class, 'crawlData'])->name('crawl_data');
Route::post('/crawl-force', [CrawlController::class, 'forceCrawlData'])->name('force_crawl_data');
