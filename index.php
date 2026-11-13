<?php
if (PHP_SAPI === 'cli-server') {
    $__requestPath = urldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
    $__requestedFile = __DIR__ . $__requestPath;
    if ($__requestPath !== '/' && is_file($__requestedFile)) {
        return false;
    }
    unset($__requestPath, $__requestedFile);
}

// Özel vurgu rengi: /theme.css?a=rrggbb  (satır içi stil CSP ile engelli olduğundan ayrı stylesheet olarak sunulur)
$__themePath = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (str_ends_with($__themePath, '/theme.css')) {
    $hex = (string)($_GET['a'] ?? '');
    header('Content-Type: text/css; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    if (!preg_match('/^[0-9a-f]{6}$/i', $hex)) {
        http_response_code(400);
        exit('/* invalid colour */');
    }
    $hex = strtolower($hex);
    [$r, $g, $b] = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    $on = ((0.299 * $r + 0.587 * $g + 0.114 * $b) / 255) > 0.6 ? '#0b0b0c' : '#ffffff';
    header('Cache-Control: public, max-age=31536000, immutable');
    echo ':root[data-accent="custom"]{--accent:#' . $hex . ';--on-accent:' . $on . ';}';
    exit;
}
unset($__themePath);

require __DIR__ . '/config.php';

Router::dispatch();