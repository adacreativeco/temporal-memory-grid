<?php
namespace Temporal {

class I18n {
    public static $SUPPORTED_LANGS = [
        'tr' => [
            'name' => 'Turkish',
            'native' => 'Türkçe',
            'flag' => '🇹🇷',
            'locale' => 'tr-TR',
            'dir' => 'ltr'
        ],
        'en' => [
            'name' => 'English',
            'native' => 'English',
            'flag' => '🇬🇧',
            'locale' => 'en-US',
            'dir' => 'ltr'
        ],
        'de' => [
            'name' => 'German',
            'native' => 'Deutsch',
            'flag' => '🇩🇪',
            'locale' => 'de-DE',
            'dir' => 'ltr'
        ],
        'es' => [
            'name' => 'Spanish',
            'native' => 'Español',
            'flag' => '🇪🇸',
            'locale' => 'es-ES',
            'dir' => 'ltr'
        ],
        'fr' => [
            'name' => 'French',
            'native' => 'Français',
            'flag' => '🇫🇷',
            'locale' => 'fr-FR',
            'dir' => 'ltr'
        ]
    ];

    private static $currentLang = 'tr';
    private static $translations = [];
    private static $fallbackTranslations = [];
    private static $initialized = false;

    public static function init() {
        if (self::$initialized) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $supportedKeys = array_keys(self::$SUPPORTED_LANGS);
        $lang = null;

        // 1. Check explicit GET parameter
        if (!empty($_GET['lang']) && in_array(strtolower($_GET['lang']), $supportedKeys, true)) {
            $lang = strtolower($_GET['lang']);
            $_SESSION['lang'] = $lang;
            if (!headers_sent()) {
                setcookie('lang', $lang, time() + 365 * 86400, '/');
            }
        }
        // 2. Check Session
        elseif (!empty($_SESSION['lang']) && in_array(strtolower($_SESSION['lang']), $supportedKeys, true)) {
            $lang = strtolower($_SESSION['lang']);
        }
        // 3. Check Cookie
        elseif (!empty($_COOKIE['lang']) && in_array(strtolower($_COOKIE['lang']), $supportedKeys, true)) {
            $lang = strtolower($_COOKIE['lang']);
            $_SESSION['lang'] = $lang;
        }
        // 4. Auto-detect from Browser Accept-Language header
        elseif (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $detected = self::detectBrowserLanguage($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            if ($detected && in_array($detected, $supportedKeys, true)) {
                $lang = $detected;
                $_SESSION['lang'] = $lang;
            }
        }

        // 5. Default Fallback
        if (!$lang) {
            $lang = 'tr';
        }

        self::$currentLang = $lang;
        self::loadLanguages($lang);
        self::$initialized = true;
    }

    private static function detectBrowserLanguage($header) {
        $languages = [];
        if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)\s*(?:;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $header, $matches)) {
            foreach ($matches[1] as $index => $langCode) {
                $q = !empty($matches[2][$index]) ? (float)$matches[2][$index] : 1.0;
                $prefix = strtolower(explode('-', $langCode)[0]);
                if (!isset($languages[$prefix]) || $q > $languages[$prefix]) {
                    $languages[$prefix] = $q;
                }
            }
            arsort($languages);
            foreach (array_keys($languages) as $code) {
                if (isset(self::$SUPPORTED_LANGS[$code])) {
                    return $code;
                }
            }
        }
        return null;
    }

    public static function loadLanguages($lang) {
        $langFile = __DIR__ . "/lang/{$lang}.php";
        $fallbackFile = __DIR__ . "/lang/en.php";

        if (file_exists($langFile)) {
            self::$translations = require $langFile;
        } else {
            self::$translations = [];
        }

        if ($lang !== 'en' && file_exists($fallbackFile)) {
            self::$fallbackTranslations = require $fallbackFile;
        } else {
            self::$fallbackTranslations = [];
        }
    }

    public static function get($key, $params = []) {
        self::init();

        if (isset(self::$translations[$key])) {
            $text = self::$translations[$key];
        } elseif (isset(self::$fallbackTranslations[$key])) {
            $text = self::$fallbackTranslations[$key];
        } else {
            $text = $key;
        }

        if (!empty($params)) {
            if (is_array($params)) {
                $hasNamed = false;
                foreach ($params as $k => $v) {
                    if (is_string($k)) {
                        $hasNamed = true;
                        $text = str_replace('{' . $k . '}', $v, $text);
                    }
                }
                if (!$hasNamed) {
                    return vsprintf($text, $params);
                }
            } else {
                return vsprintf($text, [$params]);
            }
        }

        return $text;
    }

    public static function getLang() {
        self::init();
        return self::$currentLang;
    }

    public static function getLocale() {
        self::init();
        return self::$SUPPORTED_LANGS[self::$currentLang]['locale'] ?? 'en-US';
    }

    public static function getSupportedLanguages() {
        return self::$SUPPORTED_LANGS;
    }

    public static function getAll() {
        self::init();
        return array_merge(self::$fallbackTranslations, self::$translations);
    }

    public static function getSwitchUrl($targetLang) {
        $params = $_GET;
        $params['lang'] = $targetLang;
        $uri = strtok($_SERVER['REQUEST_URI'] ?? '/index.php', '?');
        return htmlspecialchars($uri . '?' . http_build_query($params));
    }
}

// Auto-initialize on load
I18n::init();

} // End namespace Temporal

namespace {
    // Global translation helper in root namespace
    if (!function_exists('__')) {
        function __($key, ...$params) {
            return \Temporal\I18n::get($key, $params);
        }
    }
}
