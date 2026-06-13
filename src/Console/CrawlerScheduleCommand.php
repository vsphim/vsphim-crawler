<?php

namespace VsMov\Crawler\VsMovCrawler\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use VsMov\Crawler\VsMovCrawler\Crawler;
use VsMov\Crawler\VsMovCrawler\CrawlerKKPhim;
use VsMov\Crawler\VsMovCrawler\CrawlerNguonC;
use VsMov\Crawler\VsMovCrawler\CrawlerOPhim;
use VsMov\Crawler\VsMovCrawler\Option;

class CrawlerScheduleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vsmov:plugins:vsmov-crawler:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crawler movie schedule command';

    protected $logger;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->logger = Log::channel('vsmov-crawler');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if(!$this->checkCrawlerScheduleEnable()) return 0;
        $linkVsmov = sprintf('%s/danh-sach/phim-moi-cap-nhat', Option::get('domain'));
        $linkOPhim = sprintf('%s/v1/api/danh-sach/phim-moi', Option::get('domain_ophim'));
        $linkNguonC = sprintf('%s/api/films/phim-moi-cap-nhat', Option::get('domain_nguonc'));
        $linkKKPhim = sprintf('%s/danh-sach/phim-moi-cap-nhat', Option::get('domain_kkphim'));
        $data = collect();
        $page_from = Option::get('crawler_schedule_page_from', 1);
        $page_to = Option::get('crawler_schedule_page_to', 2);
        $this->logger->notice(sprintf("Crawler Page (FROM: %d | TO: %d)",  $page_from, $page_to));
        for ($i = $page_from; $i <= $page_to; $i++) {
            if(!$this->checkCrawlerScheduleEnable()) {
                $this->logger->notice(sprintf("Stop Crawler Page"));
                return 0;
            }
            if($this->checkCrawlerScheduleEnableVsMov()) {
                $response = json_decode(Http::timeout(30)->get($linkVsmov, [
                    'page' => $i
                ]), true);
                if ($response['status'] && count($response['items'] ?? [])) {
                    $this->pushItemsWithSource($data, $response['items'], 'vsmov');
                }
            }
            if($this->checkCrawlerScheduleEnableOPhim()) {
                $response = json_decode(Http::timeout(30)->get($linkOPhim, [
                    'page' => $i
                ]), true);
                if ($response['status'] && count($response['data']['items'] ?? [])) {
                    $this->pushItemsWithSource($data, $response['data']['items'], 'ophim');
                }
            }
            if($this->checkCrawlerScheduleEnableNguonC()) {
                $response = json_decode(Http::timeout(30)->get($linkNguonC, [
                    'page' => $i
                ]), true);
                if ($response['status'] && count($response['items'] ?? [])) {
                    $this->pushItemsWithSource($data, $response['items'], 'nguonc');
                }
            }
            if($this->checkCrawlerScheduleEnableKKPhim()) {
                $response = json_decode(Http::timeout(30)->get($linkKKPhim, [
                    'page' => $i
                ]), true);
                if ($response['status'] && count($response['items'] ?? [])) {
                    $this->pushItemsWithSource($data, $response['items'], 'kkphim');
                }
            }
        }
        $movies = $data->shuffle();
        $count_movies = count($movies);
        $this->logger->notice(sprintf("Start Crawler Movies (TOTAL: %d)",  $count_movies));
        $count_error = 0;
        foreach ($movies as $key => $movie) {
            try {
                if(!$this->checkCrawlerScheduleEnable()) {
                    $this->logger->notice(sprintf("Stop Crawler Movies (TOTAL: %d | CRAWED: %d | ERROR %d)", $count_movies, $key, $count_error));
                    return 0;
                }
                $this->crawlMovie($movie);
            } catch (\Exception $e) {
                $this->logger->error(sprintf("%s [%s] ERROR: %s", $movie['slug'], $movie['_source'] ?? 'vsmov', $e->getMessage()));
                $count_error++;
            }
        }
        $this->logger->notice(sprintf("Finish Crawler Movies (TOTAL: %d | DONE: %d | ERROR: %d)", $count_movies, $count_movies - $count_error, $count_error));
        return 0;
    }

    protected function pushItemsWithSource($data, array $items, string $source): void
    {
        foreach ($items as $item) {
            $data->push(array_merge($item, ['_source' => $source]));
        }
    }

    protected function crawlMovie(array $movie): void
    {
        $source = $movie['_source'] ?? 'vsmov';
        $slug = $movie['slug'];
        $fields = Option::get('crawler_schedule_fields', Option::getAllOptions()['crawler_schedule_fields']['default']);
        $excludedCategories = Option::get('crawler_schedule_excludedCategories', []);
        $excludedRegions = Option::get('crawler_schedule_excludedRegions', []);
        $excludedType = Option::get('crawler_schedule_excludedType', []);

        switch ($source) {
            case 'ophim':
                $link = sprintf('%s/v1/api/phim/%s', Option::get('domain_ophim'), $slug);
                (new CrawlerOPhim($link, $fields, $excludedCategories, $excludedRegions, $excludedType, false))->handle();
                break;
            case 'nguonc':
                $link = sprintf('%s/api/film/%s', Option::get('domain_nguonc'), $slug);
                (new CrawlerNguonC($link, $fields, $excludedCategories, $excludedRegions, $excludedType, false))->handle();
                break;
            case 'kkphim':
                $link = sprintf('%s/phim/%s', Option::get('domain_kkphim'), $slug);
                (new CrawlerKKPhim($link, $fields, $excludedCategories, $excludedRegions, $excludedType, false))->handle();
                break;
            default:
                $link = sprintf('%s/phim/%s', Option::get('domain'), $slug);
                (new Crawler($link, $fields, $excludedCategories, $excludedRegions, $excludedType, false))->handle();
        }
    }

    public function checkCrawlerScheduleEnable()
    {
        return Option::get('crawler_schedule_enable', false);
    }

    public function checkCrawlerScheduleEnableVsMov()
    {
        return Option::get('crawler_schedule_enable_vsmov', false);
    }

    public function checkCrawlerScheduleEnableOPhim()
    {
        return Option::get('crawler_schedule_enable_ophim', false);
    }

    public function checkCrawlerScheduleEnableNguonC()
    {
        return Option::get('crawler_schedule_enable_nguonc', false);
    }

    public function checkCrawlerScheduleEnableKKPhim()
    {
        return Option::get('crawler_schedule_enable_kkphim', false);
    }
}
