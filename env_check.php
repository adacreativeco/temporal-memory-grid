<?php
header('Content-Type: application/json; charset=utf-8');
$data = [
    'openssl_loaded' => extension_loaded('openssl'),
    'curl_loaded' => extension_loaded('curl'),
    'allow_url_fopen' => (bool) ini_get('allow_url_fopen'),
];
echo json_encode($data);
