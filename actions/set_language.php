<?php
require_once __DIR__ . '/../i18n.php';

header('Content-Type: application/json; charset=utf-8');

$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';
$supported = array_keys(\Temporal\I18n::$SUPPORTED_LANGS);

if (in_array(strtolower($lang), $supported, true)) {
    $lang = strtolower($lang);
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 365 * 86400, '/');

    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') === false && !empty($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'lang' => $lang,
        'locale' => \Temporal\I18n::$SUPPORTED_LANGS[$lang]['locale'],
        'message' => 'Language changed successfully'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Unsupported language code. Available: ' . implode(', ', $supported)
    ]);
}
