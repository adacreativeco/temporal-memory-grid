<?php
namespace Temporal;

class I18n {
    private static $currentLang = 'tr';
    private static $translations = [];
    private static $initialized = false;

    public static function init() {
        if (self::$initialized) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        // Language selection priority: 1. GET param, 2. Session, 3. Cookie, 4. Browser header, 5. Default (tr)
        $lang = 'tr';

        if (!empty($_GET['lang']) && in_array($_GET['lang'], ['tr', 'en'])) {
            $lang = $_GET['lang'];
            $_SESSION['lang'] = $lang;
            if (!headers_sent()) {
                setcookie('lang', $lang, time() + 365*86400, '/');
            }
        } elseif (!empty($_SESSION['lang']) && in_array($_SESSION['lang'], ['tr', 'en'])) {
            $lang = $_SESSION['lang'];
        } elseif (!empty($_COOKIE['lang']) && in_array($_COOKIE['lang'], ['tr', 'en'])) {
            $lang = $_COOKIE['lang'];
            $_SESSION['lang'] = $lang;
        }

        self::$currentLang = $lang;
        self::loadLanguage($lang);
        self::$initialized = true;
    }

    public static function loadLanguage($lang) {
        $file = __DIR__ . "/lang/{$lang}.php";
        if (file_exists($file)) {
            self::$translations = require $file;
        } else {
            self::$translations = require __DIR__ . "/lang/tr.php";
        }
    }

    public static function get($key, $params = []) {
        self::init();
        $text = self::$translations[$key] ?? $key;

        if (!empty($params)) {
            return vsprintf($text, is_array($params) ? $params : [$params]);
        }

        return $text;
    }

    public static function getLang() {
        self::init();
        return self::$currentLang;
    }

    public static function getAll() {
        self::init();
        return self::$translations;
    }
}

// Global translation helper
if (!function_exists('__')) {
    function __($key, ...$params) {
        return \Temporal\I18n::get($key, $params);
    }
}

// Auto-initialize
\Temporal\I18n::init();
