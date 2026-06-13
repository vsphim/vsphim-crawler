<?php

namespace VsMov\Crawler\VsMovCrawler;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as SP;
use VsMov\Crawler\VsMovCrawler\Console\CrawlerScheduleCommand;
use VsMov\Crawler\VsMovCrawler\Option;

class VsMovCrawlerServiceProvider extends SP
{
    /**
     * Get the policies defined on the provider.
     *
     * @return array
     */
    public function policies()
    {
        return [];
    }

    public function register()
    {

        config(['plugins' => array_merge(config('plugins', []), [
            'vsmov/vsmov-crawler' =>
            [
                'name' => 'VsMov Crawler',
                'package_name' => 'vsmov/vsmov-crawler',
                'icon' => 'la la-hand-grab-o',
                'entries' => [
                    ['name' => 'VsMov', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/vsmov-crawler')],
                    ['name' => 'OPhim', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/ophim-crawler')],
                    ['name' => 'KKPhim', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/kkphim-crawler')],
                    ['name' => 'NguonC', 'icon' => 'la la-hand-grab-o', 'url' => backpack_url('/plugin/nguonc-crawler')],
                    ['name' => 'Option', 'icon' => 'la la-cog', 'url' => backpack_url('/plugin/vsmov-crawler/options')],
                ],
            ]
        ])]);

        config(['logging.channels' => array_merge(config('logging.channels', []), [
            'vsmov-crawler' => [
                'driver' => 'daily',
                'path' => storage_path('logs/vsmov/vsmov-crawler.log'),
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 7,
            ],
        ])]);

        config(['vsmov.updaters' => array_merge(config('vsmov.updaters', []), [
            [
                'name' => 'VsMov Crawler',
                'handler' => 'VsMov\Crawler\VsMovCrawler\Crawler'
            ]
        ])]);
    }

    public function boot()
    {
        $this->commands([
            CrawlerScheduleCommand::class,
        ]);

        $this->app->booted(function () {
            $this->loadScheduler();
        });

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'vsmov-crawler');
    }

    protected function loadScheduler()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('vsmov:plugins:vsmov-crawler:schedule')->cron(Option::get('crawler_schedule_cron_config', '*/10 * * * *'))->withoutOverlapping();
    }
}
