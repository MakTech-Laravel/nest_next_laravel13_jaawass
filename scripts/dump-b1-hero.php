<?php

$html = file_get_contents(__DIR__.'/../resources/views/demo/templates/b1-welcome.html');
$html = preg_replace('/<img[^>]+>/', '', $html);
if (preg_match('/<div class="h1">(.*?)<\/div>\s*<div class="sec wh">/s', $html, $m)) {
    echo $m[1];
}
