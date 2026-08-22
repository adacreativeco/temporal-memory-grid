# ⏳ Temporal Memory Grid (TMG)

<p align="center">
  <a href="README.md">🇬🇧 English</a> •
  <a href="README.tr.md">🇹🇷 Türkçe</a>
</p>

---

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Veritabanı](https://img.shields.io/badge/Veritabanı-SQLite_%7C_MySQL-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org/)
[![Docker](https://img.shields.io/badge/Docker-Hazır-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com/)
[![Lisans](https://img.shields.io/badge/Lisans-Apache_2.0-blue?style=for-the-badge)](LICENSE)
[![Testler](https://img.shields.io/badge/Testler-22%20Geçti-success?style=for-the-badge&logo=php&logoColor=white)](tests/test_tmg.php)
[![GitHub Stars](https://img.shields.io/github/stars/adacreativeco/temporal-memory-grid?style=for-the-badge&color=ffd700)](https://github.com/adacreativeco/temporal-memory-grid/stargazers)

</div>

**Temporal Memory Grid (TMG)**, yüksek frekanslı zaman serisi verilerini toplayan, farklı zaman pencerelerinde (`1m`, `5m`, `15m`, `1h`, `1d`) toplayıp özetleyen ve anomali tespiti yapan yüksek performanslı bir zamansal veri motorudur.

<p align="center">
  <img src="docs/assets/tmg_dashboard.png" alt="Temporal Memory Grid - Ana Kontrol Paneli" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🌟 Öne Çıkan Modüller & Görseller

```mermaid
graph TD
    A[Olay Veri Akışı] --> B(Zamansal Paketleme Motoru)
    B --> C[(Zaman Dilimleri & Metrik Deposu)]
    C --> D[Çoklu Çözünürlüklü Toplama Motoru]
    C --> E[Anomali & Kural Eşik Denetleyicisi]
    D --> F[Trend & Analitik Grafikleri]
    E --> G[Gerçek Zamanlı Uyarı Bildirimi]
```

### 1. 📈 Zamansal Trendler & Tarihsel Özetler
* `1m`, `5m`, `15m`, `1h`, `1d` pencerelerinde metrik hesaplama.
* Yüzdelik dilimler (p50, p90, p95, p99), verim eğrileri ve gecikme dağılım grafikleri.

<p align="center">
  <img src="docs/assets/tmg_trends.png" alt="TMG Zaman Serisi Trendleri" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 2. 🚨 Anomali Tespiti & Eşik Kuralları
* Gecikme artışları, hata oranı sıçramaları ve hacim anomalilerini anlık izleme.
* Kural oluşturma, önem derecelendirmesi (Kritik, Uyarı, Bilgi) ve aktif/pasif kontrolleri.

<p align="center">
  <img src="docs/assets/tmg_anomalies.png" alt="TMG Anomali ve Uyarı Motoru" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 3. 📋 Sistem Logları & İşlem Geçmişi
* Zamanlanmış toplama (rollup) işlerinin ve dış veri çekici süreçlerin detaylı yürütme geçmişi.
* İşlem süreleri, işlenen kayıt sayısı ve hata telemetrisi.

<p align="center">
  <img src="docs/assets/tmg_logs.png" alt="TMG Sistem Logları" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 4. 🔑 İnteraktif API Dokümantasyonu
* Zamansal paketleri sorgulamak, manuel özetlemeleri tetiklemek ve telemetri aktarmak için REST API kılavuzu.

<p align="center">
  <img src="docs/assets/tmg_api_guide.png" alt="TMG API Kılavuzu" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 5. ⚙️ Sistem Ayarları & Güvenlik
* Kullanıcı yönetimi, API anahtarı üretimi, önbellekleme ve saat dilimi yapılandırmaları.

<p align="center">
  <img src="docs/assets/tmg_settings.png" alt="TMG Sistem Ayarları" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🚀 Hızlı Başlangıç

### 1. Gereksinimler
* PHP 8.0+ (`pdo`, `pdo_sqlite` veya `pdo_mysql` eklentileri)
* Web sunucusu (Nginx, Apache veya PHP Yerel Sunucusu)

### 2. Kurulum ve Çalıştırma
```bash
# Depoyu klonlayın
git clone https://github.com/adacreativeco/temporal-memory-grid.git
cd temporal-memory-grid

# Veritabanını başlatın
php setup_database_sqlite.php

# Geliştirme sunucusunu başlatın
php -S 127.0.0.1:8080
```

### 3. Otomatik Testleri Çalıştırma
```bash
php tests/test_tmg.php
```

---

## 📄 Lisans
Apache License 2.0 ile lisanslanmıştır. [ADA Creative Co.](https://github.com/adacreativeco) tarafından geliştirilmiştir.
