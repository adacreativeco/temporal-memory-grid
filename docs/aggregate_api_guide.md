Temporal Aggregates API Guide

Ortam Değişkenleri
- `TEMPORAL_BASE_URL`: Üretim taban URL’niz (örn. `https://tmg.adacreative.co/`)
- `TEMPORAL_API_KEY`: API anahtarınız (varsayılan: `temporal_grid_api_key_2024`)
  - Alternatif demo: `demo_key_12345`
  - Listeyi özelleştirmek için `auth.php` içindeki anahtar dizisini güncelleyin

Kimlik Doğrulama
- Query param: `api_key=temporal_grid_api_key_2024`
- Header alternatifi: `X-API-Key: temporal_grid_api_key_2024`

Genel Parametreler
- `start_time`: ISO 8601 (`YYYY-MM-DDTHH:MM:SSZ`)
- `end_time`: ISO 8601 (`YYYY-MM-DDTHH:MM:SSZ`)
- `bucket_size`: `1m`, `5m`, `15m`, `1h`, `1d`
- `format`: `json` (varsayılan) veya `csv`

Uç Noktalar
- `GET /api/v1/timeseries.php`
- `GET /api/v1/trend.php`
- `GET /api/v1/anomalies.php`

Timeseries (Aggregated Buckets)
- `metric_type`: `total_events` | `events_by_type` | `events_by_source`
- Ek parametreler:
- `type` (events_by_type için zorunlu)
- `source_id` (events_by_source için zorunlu)
- JSON Yanıt Alanları:
- `metric_type`, `type`, `source_id`, `bucket_size`, `start_time`, `end_time`
- `buckets`: `[ { bucket_start: 'YYYY-MM-DD HH:MM:SS', count: number } ]`
- Örnek İstek:
- `GET https://tmg.adacreative.co/api/v1/timeseries.php?api_key=${TEMPORAL_API_KEY}&metric_type=total_events&bucket_size=1m&start_time=2025-12-09T00:33:00Z&end_time=2025-12-09T00:35:00Z`
- Örnek Yanıt (JSON):
- `{ "success": true, "message": "Success", "data": { "metric_type": "total_events", "bucket_size": "1m", "start_time": "2025-12-09 00:33:00", "end_time": "2025-12-09 00:35:00", "buckets": [ { "bucket_start": "2025-12-09 00:33:00", "count": 10 }, { "bucket_start": "2025-12-09 00:34:00", "count": 1 } ] } }`
- CSV:
- `GET https://tmg.adacreative.co/api/v1/timeseries.php?api_key=${TEMPORAL_API_KEY}&metric_type=total_events&bucket_size=1m&start_time=2025-12-09T00:33:00Z&end_time=2025-12-09T00:35:00Z&format=csv`
- Başlıklar ve satırlar: `Bucket Start,Count`

Trend (Two-Period Comparison)
- Parametreler:
- `metric_type`: yukarıdaki değerler
- `primary_start_time`, `primary_end_time`, `compare_start_time`, `compare_end_time`
- JSON Yanıt Alanları:
- `metric_type`, `type`, `source_id`
- `primary_start_time`, `primary_end_time`, `compare_start_time`, `compare_end_time`
- `primary_count`, `compare_count`, `difference_absolute`, `difference_percent`
- Örnek (Üretim):
- `{ "success": true, "data": { "metric_type": "total_events", "primary_start_time": "2025-12-09 00:33:00", "primary_end_time": "2025-12-09 00:35:00", "compare_start_time": "2025-12-08 00:33:00", "compare_end_time": "2025-12-08 00:35:00", "primary_count": 11, "compare_count": 8, "difference_absolute": 3, "difference_percent": 37.5 } }`

Anomalies (Deviation Detection)
- Parametreler:
- `metric_type`, `bucket_size`, `start_time`, `end_time`
- `baseline`: `historical` (varsayılan) veya `moving_average`
- `deviation_threshold`: yüzde, varsayılan `50`
- `ma_window`: hareketli ortalama pencere boyutu (varsayılan `6`)
- JSON Yanıt Alanları:
- `metric_type`, `type`, `source_id`, `bucket_size`, `start_time`, `end_time`
- `deviation_threshold`, `baseline`, `ma_window`
- `anomaly_buckets`: `[ { bucket_start: 'YYYY-MM-DD HH:MM:SS', observed_value: number, expected_value: number, deviation_percent: number } ]`
- Örnek (Üretim):
- `{ "success": true, "data": { "metric_type": "total_events", "bucket_size": "1m", "start_time": "2025-12-09 00:33:00", "end_time": "2025-12-09 00:35:00", "deviation_threshold": 50, "baseline": "historical", "ma_window": 6, "anomaly_buckets": [ { "bucket_start": "2025-12-09 00:33:00", "observed_value": 10, "expected_value": 5.2, "deviation_percent": 92.31 } ] } }`

Hata Kodları
- `401`: API anahtarı yok/yanlış
- `400`: Parametre doğrulama hatası
- `429`: Rate limit aşıldı
- `500`: Sunucu hatası

Notlar
- Zaman formatı isteklerde ISO, yanıtlarda DB dostu `YYYY-MM-DD HH:MM:SS`
- `bucket_size=5m` okumaları için 1m→5m rollup türetilir; yoğun aralıklar için çekim sonrası oluşur

Postman Kullanımı
- Environment değişkenleri ekleyin:
  - `TEMPORAL_BASE_URL = https://tmg.adacreative.co`
  - `TEMPORAL_API_KEY = temporal_grid_api_key_2024`
- İsteklerde `{{TEMPORAL_BASE_URL}}` ve `{{TEMPORAL_API_KEY}}` kullanın; header olarak `X-API-Key: {{TEMPORAL_API_KEY}}` ekleyebilirsiniz.
