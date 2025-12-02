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
    'namespace'  => 'Vsphim\Crawler\VsphimCrawler\Controllers',
], function () {
    Route::get('/plugin/vsphim-crawler', 'CrawlController@showCrawlPage');
    Route::get('/plugin/vsphim-crawler/options', 'CrawlerSettingController@editOptions');
    Route::put('/plugin/vsphim-crawler/options', 'CrawlerSettingController@updateOptions');
    Route::get('/plugin/vsphim-crawler/fetch', 'CrawlController@fetch');
    Route::post('/plugin/vsphim-crawler/crawl', 'CrawlController@crawl');
    Route::post('/plugin/vsphim-crawler/get-movies', 'CrawlController@getMoviesFromParams');
});
