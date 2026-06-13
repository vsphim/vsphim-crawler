<?php

namespace VsMov\Crawler\VsMovCrawler;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\Storage;
use VsMov\Core\Models\Movie;

class Collector
{
    protected $fields;
    protected $payload;
    protected $forceUpdate;
    protected $movie;

    public function __construct(array $payload, array $fields, $forceUpdate, ?Movie $movie = null)
    {
        $this->fields = $fields;
        $this->payload = $payload;
        $this->forceUpdate = $forceUpdate;
        $this->movie = $movie;
    }

    public function get(): array
    {
        $info = $this->payload['movie'] ?? [];
        $episodes = $this->payload['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['origin_name'],
            'publish_year' => $info['year'],
            'content' => $info['content'],
            'type' =>  $this->getMovieType($info, $episodes),
            'status' => $info['status'],
            'thumb_url' => $this->resolveImageUrl($this->movie?->thumb_url, fn () => $this->getThumbImage($info['slug'], $info['thumb_url'])),
            'poster_url' => $this->resolveImageUrl($this->movie?->poster_url, fn () => $this->getPosterImage($info['slug'], $info['poster_url'])),
            'is_copyright' => $info['is_copyright'],
            'trailer_url' => $info['trailer_url'] ?? "",
            'quality' => $info['quality'],
            'language' => $info['lang'],
            'episode_time' => $info['time'],
            'episode_current' => $info['episode_current'],
            'episode_total' => $info['episode_total'],
            'notify' => $info['notify'],
            'showtimes' => $info['showtimes'],
            'is_shown_in_theater' => $info['chieurap'],
        ];

        return $data;
    }

    public function getOPhim(): array
    {
        $info = $this->payload['data']['item'] ?? [];
        $episodes = $info['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['origin_name'],
            'publish_year' => $info['year'],
            'content' => $info['content'],
            'type' =>  $this->getMovieType($info, $episodes),
            'status' => $info['status'],
            'thumb_url' => $this->resolveImageUrl($this->movie?->thumb_url, fn () => $this->getThumbImage($info['slug'], 'https://img.ophim.live/uploads/movies/' . $info['thumb_url'])),
            'poster_url' => $this->resolveImageUrl($this->movie?->poster_url, fn () => $this->getPosterImage($info['slug'], 'https://img.ophim.live/uploads/movies/' . $info['poster_url'])),
            'is_copyright' => $info['is_copyright'],
            'trailer_url' => $info['trailer_url'] ?? "",
            'quality' => $info['quality'],
            'language' => $info['lang'],
            'episode_time' => $info['time'],
            'episode_current' => $info['episode_current'],
            'episode_total' => $info['episode_total'],
            'notify' => $info['notify'],
            'showtimes' => $info['showtimes'],
            'is_shown_in_theater' => $info['chieurap'],
        ];

        return $data;
    }

    public function getKKPhim(): array
    {
        $info = $this->payload['movie'] ?? [];
        $episodes = $this->payload['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['origin_name'],
            'publish_year' => $info['year'],
            'content' => $info['content'],
            'type' =>  $this->getMovieType($info, $episodes),
            'status' => $info['status'],
            'thumb_url' => $this->resolveImageUrl($this->movie?->thumb_url, fn () => $this->getThumbImage($info['slug'], $info['poster_url'])),
            'poster_url' => $this->resolveImageUrl($this->movie?->poster_url, fn () => $this->getPosterImage($info['slug'], $info['thumb_url'])),
            'is_copyright' => $info['is_copyright'],
            'trailer_url' => $info['trailer_url'] ?? "",
            'quality' => $info['quality'],
            'language' => $info['lang'],
            'episode_time' => $info['time'],
            'episode_current' => $info['episode_current'],
            'episode_total' => $info['episode_total'],
            'notify' => $info['notify'],
            'showtimes' => $info['showtimes'],
            'is_shown_in_theater' => $info['chieurap'],
        ];

        return $data;
    }

    public function getNguonC(): array
    {
        $info = $this->payload['movie'] ?? [];
        $episodes = $info['episodes'] ?? [];

        $data = [
            'name' => $info['name'],
            'origin_name' => $info['original_name'] ?? '',
            'publish_year' => $this->getNguonCPublishYear($info),
            'content' => $info['description'] ?? '',
            'type' => $this->getNguonCMovieType($info, $episodes),
            'status' => $this->getNguonCStatus($info),
            'thumb_url' => $this->resolveImageUrl($this->movie?->thumb_url, fn () => $this->getThumbImage($info['slug'], $info['thumb_url'] ?? '')),
            'poster_url' => $this->resolveImageUrl($this->movie?->poster_url, fn () => $this->getPosterImage($info['slug'], $info['poster_url'] ?? '')),
            'is_copyright' => $info['is_copyright'] ?? false,
            'trailer_url' => $info['trailer_url'] ?? '',
            'quality' => $info['quality'] ?? '',
            'language' => $info['language'] ?? '',
            'episode_time' => $info['time'] ?? '',
            'episode_current' => $info['current_episode'] ?? '',
            'episode_total' => $info['total_episodes'] ?? '',
            'notify' => $info['notify'] ?? '',
            'showtimes' => $info['showtimes'] ?? '',
            'is_shown_in_theater' => $info['chieurap'] ?? false,
        ];

        return $data;
    }

    public function getNguonCMovieTypeFromPayload(): string
    {
        $info = $this->payload['movie'] ?? [];

        return $this->getNguonCMovieType($info, $info['episodes'] ?? []);
    }

    public function getNguonCPublishYearFromPayload(): string
    {
        return $this->getNguonCPublishYear($this->payload['movie'] ?? []);
    }

    protected function getNguonCMovieType(array $info, array $episodes): string
    {
        $formats = $this->getNguonCCategoryList($info, 'Định dạng');

        if (in_array('Phim bộ', $formats, true)) {
            return 'series';
        }

        if (in_array('Phim lẻ', $formats, true)) {
            return 'single';
        }

        $items = reset($episodes)['items'] ?? [];

        return count($items) > 1 ? 'series' : 'single';
    }

    protected function getNguonCPublishYear(array $info): string
    {
        $years = $this->getNguonCCategoryList($info, 'Năm');

        return $years[0] ?? '';
    }

    protected function getNguonCStatus(array $info): string
    {
        $formats = $this->getNguonCCategoryList($info, 'Định dạng');
        $typeNames = ['Phim bộ', 'Phim lẻ', 'Hoạt hình', 'TV Shows'];

        foreach ($formats as $name) {
            if (in_array($name, $typeNames, true)) {
                continue;
            }

            if ($status = $this->mapNguonCStatusName($name)) {
                return $status;
            }
        }

        return $this->resolveNguonCStatusFromEpisodes(
            $info['current_episode'] ?? '',
            $info['total_episodes'] ?? 0
        );
    }

    protected function mapNguonCStatusName(string $name): ?string
    {
        $normalized = Str::lower(Str::ascii($name));

        if (str_contains($normalized, 'dang chieu') || str_contains($normalized, 'ongoing')) {
            return 'ongoing';
        }

        if (str_contains($normalized, 'hoan tat') || str_contains($normalized, 'hoan thanh') || str_contains($normalized, 'completed')) {
            return 'completed';
        }

        if (str_contains($normalized, 'sap chieu') || str_contains($normalized, 'trailer')) {
            return 'trailer';
        }

        return null;
    }

    protected function resolveNguonCStatusFromEpisodes(string $currentEpisode, $totalEpisodes): string
    {
        $currentEpisode = trim($currentEpisode);
        $totalEpisodes = (int) $totalEpisodes;

        if ($currentEpisode === '') {
            return 'ongoing';
        }

        if (preg_match('/hoàn tất\s*\((\d+)\/(\d+)\)/iu', $currentEpisode, $matches)) {
            return (int) $matches[1] >= (int) $matches[2] ? 'completed' : 'ongoing';
        }

        if (preg_match('/hoàn tất|hoàn thành/iu', $currentEpisode)) {
            return 'completed';
        }

        if (preg_match('/tập\s*(\d+)/iu', $currentEpisode, $matches)) {
            $current = (int) $matches[1];

            if ($totalEpisodes > 0 && $current >= $totalEpisodes) {
                return 'completed';
            }

            return 'ongoing';
        }

        return 'ongoing';
    }

    public function getNguonCCategoryList(array $info, string $groupName): array
    {
        $names = [];

        foreach ($info['category'] ?? [] as $group) {
            if (($group['group']['name'] ?? '') !== $groupName) {
                continue;
            }

            foreach ($group['list'] ?? [] as $item) {
                if (!empty($item['name'])) {
                    $names[] = trim($item['name']);
                }
            }
        }

        return $names;
    }

    protected function resolveImageUrl($existingUrl, callable $fetch): string
    {
        $existingUrl = $this->normalizeImageUrl($existingUrl);

        if (!$this->forceUpdate && $existingUrl !== '') {
            return $existingUrl;
        }

        return $fetch();
    }

    protected function normalizeImageUrl($url): string
    {
        if (is_string($url)) {
            return $url;
        }

        if (is_array($url)) {
            foreach ($url as $item) {
                if (is_string($item) && $item !== '') {
                    return $item;
                }
            }
        }

        return '';
    }

    public function getThumbImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $this->normalizeImageUrl($url),
            Option::get('should_resize_thumb', false),
            Option::get('resize_thumb_width'),
            Option::get('resize_thumb_height')
        );
    }

    public function getPosterImage($slug, $url)
    {
        return $this->getImage(
            $slug,
            $this->normalizeImageUrl($url),
            Option::get('should_resize_poster', false),
            Option::get('resize_poster_width'),
            Option::get('resize_poster_height')
        );
    }


    protected function getMovieType($info, $episodes)
    {
        return $info['type'] == 'series' ? 'series'
            : ($info['type'] == 'single' ? 'single'
                : (count(reset($episodes)['server_data'] ?? []) > 1 ? 'series' : 'single'));
    }

    protected function getImage($slug, string $url, $shouldResize = false, $width = null, $height = null): string
    {
        $url = $this->normalizeImageUrl($url);

        if (!Option::get('download_image', false) || $url === '') {
            return $url;
        }
        try {
            $url = strtok($url, '?');
            $filename = substr($url, strrpos($url, '/') + 1);
            $path = "images/{$slug}/{$filename}";

            if (Storage::disk('public')->exists($path) && $this->forceUpdate == false) {
                return Storage::url($path);
            }

            // Khởi tạo curl để tải về hình ảnh
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Safari/537.36");
            $image_data = curl_exec($ch);
            curl_close($ch);

            $img = Image::make($image_data);

            if ($shouldResize) {
                $img->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            Storage::disk('public')->put($path, null);

            $img->save(storage_path("app/public/" . $path));

            return Storage::url($path);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $url;
        }
    }
}
