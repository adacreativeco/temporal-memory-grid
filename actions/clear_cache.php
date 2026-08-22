<?php
use Temporal\Auth;
use Temporal\Cache;
require_once '../auth.php';
require_once '../cache.php';
Auth::getInstance()->requireLogin();
$cache = Cache::getInstance();
$cache->clear();
echo "OK";
