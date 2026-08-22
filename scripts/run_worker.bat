@echo off
title Temporal Memory Grid - Worker Daemon
cd /d "%~dp0\.."
echo ========================================================
echo   Starting Temporal Memory Grid Background Worker...
echo   Press Ctrl+C to stop.
echo ========================================================
php worker.php --interval=10 --simulate
pause
