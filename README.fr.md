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
  <img src="https://img.shields.io/badge/Base%20de%20donn%C3%A9es-SQLite%20%7C%20MySQL-003B57?style=for-the-badge&logo=sqlite&logoColor=white" alt="Database">
  <img src="https://img.shields.io/badge/Frontend-TailwindCSS%20%2B%20Chart.js-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Frontend">
  <img src="https://img.shields.io/badge/Streaming-Server--Sent%20Events%20(SSE)-FF4500?style=for-the-badge" alt="SSE">
  <img src="https://img.shields.io/badge/i18n-5%20Langues%20(TR%20%7C%20EN%20%7C%20DE%20%7C%20ES%20%7C%20FR)-8A2BE2?style=for-the-badge" alt="i18n">
  <img src="https://img.shields.io/badge/Licence-Apache%202.0-blue.svg?style=for-the-badge" alt="Licence Apache 2.0">
</p>

**Temporal Memory Grid (TMG)** est une plateforme légère et ultra-performante d'ingestion d'événements de séries temporelles, de découpage temporel (bucketing), d'agrégation, de comparaison de tendances et de détection d'anomalies conçue avec PHP 8, SQLite/MySQL, TailwindCSS et Chart.js.

Elle s'intègre parfaitement aux flux d'événements géospatiaux en direct (comme *Realtime Map Event Grid*), agrège les événements bruts à haute fréquence dans des intervalles multi-niveaux (`1m`, `5m`, `15m`, `1h`, `1d`), détecte les anomalies et propose un flux de données en direct via SSE.

---

## 📑 Table des Matières
- [Architecture](#-architecture)
- [Fonctionnalités Principales](#-fonctionnalités-principales)
- [Démarrage Rapide](#-démarrage-rapide)
- [Automatisation & Worker](#-automatisation--worker)
- [Flux en Temps Réel (SSE)](#-flux-en-temps-réel-sse)
- [Référence de l'API REST](#-référence-de-lapi-rest)
- [Structure du Projet](#-structure-du-projet)
- [Licence & Auteurs](#-licence--auteurs)

---

## ✨ Fonctionnalités Principales

1. **Moteur d'Agrégation et de Découpage Temporel**
   - Regroupement automatique en intervalles de `1m`, `5m`, `15m`, `1h`, `1d`.
   - Calcul des métriques : `total_events`, `events_by_type`, `events_by_source` et `events_by_geo_region`.
2. **Tableau de Bord Interactif**
   - Graphiques réactifs propulsés par Chart.js et cartes d'indicateurs KPI.
3. **Comparaison de Tendances sur Deux Périodes**
   - Analyse comparative avec calcul des écarts absolus et relatifs.
4. **Détection d'Anomalies**
   - Détection des pics anormaux par rapport à la moyenne historique ou moyenne mobile (MA).
5. **Worker Automatisé en Arrière-plan**
   - Daemon CLI autonome avec suivi de battement de cœur (heartbeat) et mode crontab (`--once`).
6. **Support Multilingue Entreprise (i18n)**
   - 5 langues intégrées : 🇹🇷 Turc, 🇬🇧 Anglais, 🇩🇪 Allemand, 🇪🇸 Espagnol, 🇫🇷 Français.

---

## 🚀 Démarrage Rapide

```bash
# 1. Initialiser la base de données
php setup_database_sqlite.php

# 2. Démarrer le serveur local
php -S localhost:8000
```
- **Connexion :** `http://localhost:8000/login.php` (Identifiant : `admin` / Mot de passe : `temporal123`)

---

## 📄 Licence & Auteurs

- **Auteur & Maintenance :** **ADA Creative Co.** ([https://adacreative.co](https://adacreative.co))
- **Contact :** [git@adacreative.co](mailto:git@adacreative.co)
- **Licence :** Distribué sous licence [Apache License 2.0](LICENSE).
