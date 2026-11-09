<?php

declare(strict_types=1);

$secret = $_GET['key'] ?? '';
$expected = getenv('OPCACHE_CLEAR_KEY') ?: 'zmien-mnie-na-losowy-ciag';

if ($secret !== $expected || $expected === 'zmien-mnie-na-losowy-ciag') {
    header('HTTP/1.1 403 Forbidden');
    exit('Forbidden');
}

if (!function_exists('opcache_reset')) {
    header('Content-Type: text/plain; charset=utf-8');
    exit('OPcache nie jest włączony.');
}

opcache_reset();
header('Content-Type: text/plain; charset=utf-8');
echo 'OPcache wyczyszczony. Serwer widzi teraz nowe pliki.';

