<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\Base.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix'     => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace'  => 'VsMov\Crawler\VsMovCrawler\Controllers',
], function () {
    Route::get('/plugin/vsmov-crawler', 'CrawlController@showCrawlPage');
    Route::get('/plugin/ophim-crawler', 'CrawlController@showOPhimPage');
    Route::get('/plugin/kkphim-crawler', 'CrawlController@showKKPhimPage');
    Route::get('/plugin/nguonc-crawler', 'CrawlController@showNguonCPage');
    Route::get('/plugin/vsmov-crawler/options', 'CrawlerSettingController@editOptions');
    Route::put('/plugin/vsmov-crawler/options', 'CrawlerSettingController@updateOptions');
    Route::get('/plugin/vsmov-crawler/fetch', 'CrawlController@fetch');
    Route::get('/plugin/vsmov-crawler/fetch-ophim', 'CrawlController@fetchOPhim');
    Route::get('/plugin/vsmov-crawler/fetch-kkphim', 'CrawlController@fetchKKPhim');
    Route::get('/plugin/vsmov-crawler/fetch-nguonc', 'CrawlController@fetchNguonC');
    Route::post('/plugin/vsmov-crawler/crawl', 'CrawlController@crawl');
    Route::post('/plugin/vsmov-crawler/crawl-ophim', 'CrawlController@crawlOPhim');
    Route::post('/plugin/vsmov-crawler/crawl-kkphim', 'CrawlController@crawlKKPhim');
    Route::post('/plugin/vsmov-crawler/crawl-nguonc', 'CrawlController@crawlNguonC');
    Route::post('/plugin/vsmov-crawler/get-movies', 'CrawlController@getMoviesFromParams');
});
