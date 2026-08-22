# ⏳ Temporal Memory Grid (TMG)

<p align="center">
  <a href="README.md">🇬🇧 English</a> •
  <a href="README.tr.md">🇹🇷 Türkçe</a>
</p>

---

<div align="center">

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![Database](https://img.shields.io/badge/Database-SQLite_%7C_MySQL-003B57?style=for-the-badge&logo=sqlite&logoColor=white)](https://sqlite.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://docker.com/)
[![License](https://img.shields.io/badge/License-Apache_2.0-blue?style=for-the-badge)](LICENSE)
[![Tests](https://img.shields.io/badge/Tests-22%20Passed-success?style=for-the-badge&logo=php&logoColor=white)](tests/test_tmg.php)
[![GitHub Stars](https://img.shields.io/github/stars/adacreativeco/temporal-memory-grid?style=for-the-badge&color=ffd700)](https://github.com/adacreativeco/temporal-memory-grid/stargazers)

</div>

**Temporal Memory Grid (TMG)** is a high-performance temporal aggregation, time-series analysis, and anomaly detection engine. It continuously ingests high-frequency spatial and system events, derives multi-resolution time-buckets (`1m`, `5m`, `15m`, `1h`, `1d`), computes rolling percentile metrics, and evaluates threshold-based anomaly rules in real time.

<p align="center">
  <img src="docs/assets/tmg_dashboard.png" alt="Temporal Memory Grid - Dashboard" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🌟 Visual Showcase & Key Modules

```mermaid
graph TD
    A[Event Ingestion Stream] --> B(Temporal Bucket Engine)
    B --> C[(Time-Buckets & Metrics Store)]
    C --> D[Multi-Resolution Rollup Aggregator]
    C --> E[Anomaly & Rule Threshold Engine]
    D --> F[Trends & Analytical Dashboards]
    E --> G[Real-Time Alert Dispatcher]
```

### 1. 📈 Time-Series Trends & Historical Rollups
- Multi-resolution aggregation over `1m`, `5m`, `15m`, `1h`, and `1d` intervals.
- Rolling percentiles (p50, p90, p95, p99), throughput curves, and latency distribution charts.

<p align="center">
  <img src="docs/assets/tmg_trends.png" alt="TMG Historical Trends & Rollups" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 2. 🚨 Anomaly Detection & Rule Thresholds
- Dynamic threshold monitoring for latency spikes, error rate escalations, and volume anomalies.
- Granular rule creation, severity classifications (Critical, Warning, Info), and active status toggles.

<p align="center">
  <img src="docs/assets/tmg_anomalies.png" alt="TMG Anomaly Detection & Alerts" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 3. 📋 Ingestion Logs & Aggregation History
- Comprehensive execution history for scheduled aggregation jobs and external puller workers.
- Execution duration tracking, processed record counts, and error telemetry.

<p align="center">
  <img src="docs/assets/tmg_logs.png" alt="TMG Ingestion Logs" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 4. 🔑 API Governance & Interactive Documentation
- Complete REST API specification for querying temporal buckets, triggering manual rollups, and streaming telemetry.

<p align="center">
  <img src="docs/assets/tmg_api_guide.png" alt="TMG API Documentation" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

### 5. ⚙️ System Settings & Security
- Multi-user authentication, API key generation, caching policies, and timezone configurations.

<p align="center">
  <img src="docs/assets/tmg_settings.png" alt="TMG System Settings" width="100%" style="border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.4);" />
</p>

---

## 🚀 Quick Start

### 1. Requirements
- PHP 8.0+ (with `pdo`, `pdo_sqlite` or `pdo_mysql`)
- Web server (Nginx, Apache, or PHP Built-in Server)

### 2. Installation & Run
```bash
# Clone the repository
git clone https://github.com/adacreativeco/temporal-memory-grid.git
cd temporal-memory-grid

# Initialize the database
php setup_database_sqlite.php

# Start development server
php -S 127.0.0.1:8080
```

### 3. Run Automated Tests
```bash
php tests/test_tmg.php
```

---

## 📄 License
Licensed under the Apache License 2.0. Developed with 🧠 by [ADA Creative Co.](https://github.com/adacreativeco).
