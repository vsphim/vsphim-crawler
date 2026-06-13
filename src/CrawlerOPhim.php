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

class CrawlerOPhim extends BaseCrawler
{
    public function handle()
    {
        $payload = json_decode($body = file_get_contents($this->link), true);

        $this->checkIsInExcludedList($payload);

        $movie = Movie::where('name', $payload['data']['item']['name'])
            ->where('type', $payload['data']['item']['type'])
            ->where('publish_year', $payload['data']['item']['year'])
            ->first();

        if(!$movie) {
            $movie = Movie::where('origin_name', $payload['data']['item']['origin_name'])
                ->where('publish_year', $payload['data']['item']['year'])
                ->where('type', $payload['data']['item']['type'])
                ->first();

            if(!$movie) {
                $movie = Movie::where('slug', $payload['data']['item']['slug'])
                    ->where('type', $payload['data']['item']['type'])
                    ->where('publish_year', $payload['data']['item']['year'])
                    ->first();
            }
        }

        if (!$this->hasChange($movie, md5($body)) && $this->forceUpdate == false) {
            return false;
        }

        $info = (new Collector($payload, $this->fields, $this->forceUpdate, $movie))->getOPhim();

        if ($movie) {
            $movie->updated_at = now();
            $movie->update(collect($info)->only($this->fields)->merge(['update_checksum' => md5($body)])->toArray());
        } else {
            $movie = Movie::create(array_merge($info, [
                'update_handler' => static::class,
                'update_identity' => $payload['data']['item']['_id'],
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
        $newType = $payload['data']['item']['type'];
        if (in_array($newType, $this->excludedType)) {
            throw new \Exception("Thuộc định dạng đã loại trừ");
        }

        $newCategories = collect($payload['data']['item']['category'])->pluck('name')->toArray();
        if (array_intersect($newCategories, $this->excludedCategories)) {
            throw new \Exception("Thuộc thể loại đã loại trừ");
        }

        $newRegions = collect($payload['data']['item']['country'])->pluck('name')->toArray();
        if (array_intersect($newRegions, $this->excludedRegions)) {
            throw new \Exception("Thuộc quốc gia đã loại trừ");
        }
    }

    protected function syncActors($movie, array $payload)
    {
        if (!in_array('actors', $this->fields)) return;

        $actors = [];
        foreach ($payload['data']['item']['actor'] as $actor) {
            if (!trim($actor)) continue;
            $actors[] = Actor::firstOrCreate(['name' => trim($actor)])->id;
        }
        $movie->actors()->sync($actors);
    }

    protected function syncDirectors($movie, array $payload)
    {
        if (!in_array('directors', $this->fields)) return;

        $directors = [];
        foreach ($payload['data']['item']['director'] as $director) {
            if (!trim($director)) continue;
            $directors[] = Director::firstOrCreate(['name' => trim($director)])->id;
        }
        $movie->directors()->sync($directors);
    }

    protected function syncCategories($movie, array $payload)
    {
        if (!in_array('categories', $this->fields)) return;
        $categories = [];
        foreach ($payload['data']['item']['category'] as $category) {
            if (!trim($category['name'])) continue;
            $categories[] = Category::firstOrCreate(['name' => trim($category['name'])])->id;
        }
        if($payload['data']['item']['type'] === 'hoathinh') $categories[] = Category::firstOrCreate(['name' => 'Hoạt Hình'])->id;
        if($payload['data']['item']['type'] === 'tvshows') $categories[] = Category::firstOrCreate(['name' => 'TV Shows'])->id;
        $movie->categories()->sync($categories);
    }

    protected function syncRegions($movie, array $payload)
    {
        if (!in_array('regions', $this->fields)) return;

        $regions = [];
        foreach ($payload['data']['item']['country'] as $region) {
            if (!trim($region['name'])) continue;
            $regions[] = Region::firstOrCreate(['name' => trim($region['name'])])->id;
        }
        $movie->regions()->sync($regions);
    }

    protected function syncTags($movie, array $payload)
    {
        if (!in_array('tags', $this->fields)) return;

        $tags = [];
        $tags[] = Tag::firstOrCreate(['name' => trim($payload['data']['item']['name'])])->id;
        $tags[] = Tag::firstOrCreate(['name' => trim($payload['data']['item']['origin_name'])])->id;

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
        foreach ($payload['data']['item']['episodes'] as $server) {
            foreach ($server['server_data'] as $episode) {
                $serverName = $server['server_name'] . ' (OP)';
                if (!empty($episode['link_m3u8'])) {
                    Episode::updateOrCreate([
                        'id' => $movie->episodes[$flag]->id ?? null,
                        'server' => $serverName
                    ], [
                        'name' => $episode['name'],
                        'movie_id' => $movie->id,
                        'type' => 'm3u8',
                        'link' => $episode['link_m3u8'],
                        'slug' => 'tap-' . Str::slug($episode['name'])
                    ]);
                    $flag++;
                }
                if (!empty($episode['link_embed'])) {
                    Episode::updateOrCreate([
                        'id' => $movie->episodes[$flag]->id ?? null,
                        'server' => $serverName
                    ], [
                        'name' => $episode['name'],
                        'movie_id' => $movie->id,
                        'type' => 'embed',
                        'link' => $episode['link_embed'],
                        'slug' => 'tap-' . Str::slug($episode['name'])
                    ]);
                    $flag++;
                }
            }
        }
        for ($i=$flag; $i < count($movie->episodes); $i++) {
            $movie->episodes[$i]->delete();
        }
    }
}
