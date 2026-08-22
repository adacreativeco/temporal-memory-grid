<?php
require_once __DIR__ . '/auth.php';
\Temporal\Auth::getInstance()->logout();
header('Location: /login.php');
exit();
