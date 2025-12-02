# Vsphim Crawler

## Demo
### Crawl Page
![Alt text](https://i.ibb.co/WPy9Hp7/CRAWLER-INDEX.png "Crawler Page")

### Options Page
![Alt text](https://i.ibb.co/zmDYwRd/CRAWLER-OPTION.png "Options Page")

### Schedule Config
![Alt text](https://i.ibb.co/5jY3s2P/CRAWLER-SCHEDULE.png "Options Page")

## Requirements
https://github.com/vsphim/vsphim-core

## Install
- In your project folder: `composer require vsphim/vsphim-crawler`

## Update
- In your project folder: `composer update vsphim/vsphim-crawler`

## API Source
- Default API domain: `https://nguon.vsphim.com/api-document`
- Endpoints are compatible with original Ophim API:
  - `/danh-sach/phim-moi-cap-nhat`
  - `/the-loai`
  - `/quoc-gia`
  - `/phim/{slug}`

## Setup Crontab
Use the `vsphim:plugins:vsphim-crawler:schedule` command in your crontab, for example:

```bash
*/10 * * * * php /path/to/artisan vsphim:plugins:vsphim-crawler:schedule >> /dev/null 2>&1
```

## Changelog
### 2.0.0
- Rebranded to Vsphim
- Switched to `vsphim/vsphim-core`
- Default API domain changed to `https://nguon.vsphim.com/api-document`

### 1.1.0
- Update crawler schedule
### 1.0.3
- Fix Logic save field crawler
### 1.0.2
- enable check hasChange
### 1.0.1
- Fix sync episodes
### 23/09/2022
- Ghi nhớ fields crawl + download images
- Fix crawl pages hạn chế timeout khi nhiều page

### 22/09/2022
- Thêm lọc bỏ qua theo định dạng
- Tạo thể loại đối với định dạng là `hoạt hình` và `tv shows`
