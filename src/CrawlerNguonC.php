<?php

namespace VsMov\Crawler\VsMovCrawler;

use VsMov\Core\Models\Movie;
use Illuminate\Support\Str;
use VsMov\Core\Models\Actor;
use VsMov\Core\Models\Category;
use VsMov\Core\Models\Director;
use VsMov\Core\Models\Episode;
use VsMov\Core\Models\Region;
use VsMov\Core\Models\Tag;
use VsMov\Crawler\VsMovCrawler\Contracts\BaseCrawler;

class CrawlerNguonC extends BaseCrawler
{
    public function handle()
    {
        $payload = json_decode($body = file_get_contents($this->link), true);

        $this->checkIsInExcludedList($payload);

        $collector = new Collector($payload, $this->fields, $this->forceUpdate);
        $movieType = $collector->getNguonCMovieTypeFromPayload();
        $publishYear = $collector->getNguonCPublishYearFromPayload();

        $movie = Movie::where('name', $payload['movie']['name'])
            ->where('type', $movieType)
            ->where('publish_year', $publishYear)
            ->first();

        if (!$movie) {
            $movie = Movie::where('origin_name', $payload['movie']['original_name'] ?? '')
                ->where('publish_year', $publishYear)
                ->where('type', $movieType)
                ->first();

            if (!$movie) {
                $movie = Movie::where('slug', $payload['movie']['slug'])
                    ->where('type', $movieType)
                    ->where('publish_year', $publishYear)
                    ->first();
            }
        }

        if (!$this->hasChange($movie, md5($body)) && $this->forceUpdate == false) {
            return false;
        }

        $info = (new Collector($payload, $this->fields, $this->forceUpdate, $movie))->getNguonC();

        if ($movie) {
            $movie->updated_at = now();
            $movie->update(collect($info)->only($this->fields)->merge(['update_checksum' => md5($body)])->toArray());
        } else {
            $movie = Movie::create(array_merge($info, [
                'update_handler' => static::class,
                'update_identity' => $payload['movie']['id'],
                'update_checksum' => md5($body)
            ]));
        }

        $this->syncActors($movie, $payload);
        $this->syncDirectors($movie, $payload);
        $this->syncCategories($movie, $payload);
        $this->syncRegions($movie, $payload);
        $this->syncTags($movie, $payload);
        $this->syncStudios($movie, $payload);
        $this->updateEpisodes($movie, $payload);
    }

    protected function hasChange(?Movie $movie, $checksum)
    {
        return is_null($movie) || ($movie->update_checksum != $checksum);
    }

    protected function checkIsInExcludedList($payload)
    {
        $collector = new Collector($payload, [], false);
        $newType = $collector->getNguonCMovieTypeFromPayload();
        if (in_array($newType, $this->excludedType)) {
            throw new \Exception("Thuộc định dạng đã loại trừ");
        }

        $newCategories = $this->getCategoryNames($payload);
        if (array_intersect($newCategories, $this->excludedCategories)) {
            throw new \Exception("Thuộc thể loại đã loại trừ");
        }

        $newRegions = $this->getRegionNames($payload);
        if (array_intersect($newRegions, $this->excludedRegions)) {
            throw new \Exception("Thuộc quốc gia đã loại trừ");
        }
    }

    protected function getCategoryNames(array $payload): array
    {
        $collector = new Collector($payload, [], false);

        return $collector->getNguonCCategoryList($payload['movie'] ?? [], 'Thể loại');
    }

    protected function getRegionNames(array $payload): array
    {
        $collector = new Collector($payload, [], false);

        return $collector->getNguonCCategoryList($payload['movie'] ?? [], 'Quốc gia');
    }

    protected function syncActors($movie, array $payload)
    {
        if (!in_array('actors', $this->fields)) return;

        $actors = [];
        foreach (explode(',', $payload['movie']['casts'] ?? '') as $actor) {
            if (!trim($actor)) continue;
            $actors[] = Actor::firstOrCreate(['name' => trim($actor)])->id;
        }
        $movie->actors()->sync($actors);
    }

    protected function syncDirectors($movie, array $payload)
    {
        if (!in_array('directors', $this->fields)) return;

        $directors = [];
        foreach (explode(',', $payload['movie']['director'] ?? '') as $director) {
            if (!trim($director)) continue;
            $directors[] = Director::firstOrCreate(['name' => trim($director)])->id;
        }
        $movie->directors()->sync($directors);
    }

    protected function syncCategories($movie, array $payload)
    {
        if (!in_array('categories', $this->fields)) return;

        $categories = [];
        foreach ($this->getCategoryNames($payload) as $category) {
            if (!trim($category)) continue;
            $categories[] = Category::firstOrCreate(['name' => trim($category)])->id;
        }
        $movie->categories()->sync($categories);
    }

    protected function syncRegions($movie, array $payload)
    {
        if (!in_array('regions', $this->fields)) return;

        $regions = [];
        foreach ($this->getRegionNames($payload) as $region) {
            if (!trim($region)) continue;
            $regions[] = Region::firstOrCreate(['name' => trim($region)])->id;
        }
        $movie->regions()->sync($regions);
    }

    protected function syncTags($movie, array $payload)
    {
        if (!in_array('tags', $this->fields)) return;

        $tags = [];
        $tags[] = Tag::firstOrCreate(['name' => trim($movie->name)])->id;
        $tags[] = Tag::firstOrCreate(['name' => trim($movie->origin_name)])->id;

        $movie->tags()->sync($tags);
    }

    protected function syncStudios($movie, array $payload)
    {
        if (!in_array('studios', $this->fields)) return;
    }

    protected function updateEpisodes($movie, $payload)
    {
        if (!in_array('episodes', $this->fields)) return;
        $flag = 0;
        foreach ($payload['movie']['episodes'] ?? [] as $server) {
            foreach ($server['items'] ?? [] as $episode) {
                $serverName = $server['server_name'] . ' (NC)';
                // if (!empty($episode['m3u8'])) {
                //     Episode::updateOrCreate([
                //         'id' => $movie->episodes[$flag]->id ?? null,
                //         'server' => $serverName
                //     ], [
                //         'name' => $episode['name'],
                //         'movie_id' => $movie->id,
                //         'type' => 'm3u8',
                //         'link' => $episode['m3u8'],
                //         'slug' => 'tap-' . Str::slug($episode['name'])
                //     ]);
                //     $flag++;
                // }
                if (!empty($episode['embed'])) {
                    Episode::updateOrCreate([
                        'id' => $movie->episodes[$flag]->id ?? null,
                        'server' => $serverName
                    ], [
                        'name' => $episode['name'],
                        'movie_id' => $movie->id,
                        'type' => 'embed',
                        'link' => $episode['embed'],
                        'slug' => 'tap-' . Str::slug($episode['name'])
                    ]);
                    $flag++;
                }
            }
        }
        for ($i = $flag; $i < count($movie->episodes); $i++) {
            $movie->episodes[$i]->delete();
        }
    }
}
