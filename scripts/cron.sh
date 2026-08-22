#!/usr/bin/env bash
# Temporal Memory Grid — Cron Runner Script
# Add to crontab via: * * * * * /path/to/scripts/cron.sh >> /var/log/tmg_worker.log 2>&1

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." && pwd )"
cd "$DIR"

php worker.php --once --simulate
