# Temporal Memory Grid (TMG)

<p align="center">
  <a href="README.md">🇬🇧 <b>English</b></a> |
  <a href="README.tr.md">🇹🇷 <b>Türkçe</b></a> |
  <a href="README.de.md">🇩🇪 <b>Deutsch</b></a> |
  <a href="README.es.md">🇪🇸 <b>Español</b></a> |
  <a href="README.fr.md">🇫🇷 <b>Français</b></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.0+">
  <img src="https://img.shields.io/badge/Base%20de%20Datos-SQLite%20%7C%20MySQL-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="Database">
  <img src="https://img.shields.io/badge/Frontend-TailwindCSS%20%2B%20Chart.js-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Frontend">
  <img src="https://img.shields.io/badge/Streaming-Server--Sent%20Events%20(SSE)-FF4500?style=for-the-badge" alt="SSE">
  <img src="https://img.shields.io/badge/i18n-5%20Idiomas%20(TR%20%7C%20EN%20%7C%20DE%20%7C%20ES%20%7C%20FR)-8A2BE2?style=for-the-badge" alt="i18n">
  <img src="https://img.shields.io/badge/Licencia-Apache%202.0-blue.svg?style=for-the-badge" alt="Licencia Apache 2.0">
</p>

**Temporal Memory Grid (TMG)** es una plataforma ligera y de alto rendimiento para la ingesta de eventos de series temporales, particionamiento temporal (bucketing), agregación, comparación de tendencias y detección de anomalías desarrollada con PHP 8, SQLite/MySQL, TailwindCSS y Chart.js.

Se conecta de forma transparente a flujos de datos geoespaciales en vivo (como *Realtime Map Event Grid*), agrega eventos en intervalos de tiempo (`1m`, `5m`, `15m`, `1h`, `1d`), detecta anomalías y ofrece gráficos interactivos con transmisión SSE en tiempo real.

---

## 📑 Tabla de Contenidos
- [Arquitectura](#-arquitectura)
- [Características Principales](#-características-principales)
- [Inicio Rápido](#-inicio-rápido)
- [Automatización y Worker](#-automatización-y-worker)
- [Transmisión en Tiempo Real (SSE)](#-transmisión-en-tiempo-real-sse)
- [Referencia de la API REST](#-referencia-de-la-api-rest)
- [Estructura del Directorio](#-estructura-del-directorio)
- [Licencia y Autores](#-licencia-y-autores)

---

## ✨ Características Principales

1. **Motor de Particionamiento Temporal y Agregación**
   - Agrupa automáticamente eventos crudos en intervalos de `1m`, `5m`, `15m`, `1h`, `1d`.
   - Calcula métricas: `total_events`, `events_by_type`, `events_by_source` y `events_by_geo_region`.
2. **Panel de Series Temporales Interactivo**
   - Gráficos responsivos basados en Chart.js y tarjetas de resumen KPI.
3. **Comparación de Tendencias en Dos Períodos**
   - Análisis comparativo con diferencias absolutas y porcentuales.
4. **Detección de Anomalías**
   - Detección de picos inusuales mediante Promedio Histórico o Media Móvil (MA).
5. **Worker en Segundo Plano Automatizado**
   - Daemon CLI independiente con monitor de latido y modo crontab (`--once`).
6. **Soporte Multilingüe Empresarial (i18n)**
   - 5 idiomas: 🇹🇷 Turco, 🇬🇧 Inglés, 🇩🇪 Alemán, 🇪🇸 Español, 🇫🇷 Francés.

---

## 🚀 Inicio Rápido

```bash
# 1. Configurar Base de Datos
php setup_database_sqlite.php

# 2. Iniciar Servidor Web
php -S localhost:8000
```
- **Panel:** `http://localhost:8000/login.php` (Usuario: `admin` / Contraseña: `temporal123`)

---

## 📄 Licencia y Autores

- **Autor y Mantenimiento:** **ADA Creative Co.** ([https://adacreative.co](https://adacreative.co))
- **Contacto:** [git@adacreative.co](mailto:git@adacreative.co)
- **Licencia:** Licenciado bajo la [Licencia Apache 2.0](LICENSE).
