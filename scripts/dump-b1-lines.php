<?php

$html = file_get_contents(__DIR__.'/../resources/views/demo/templates/b1-welcome.html');
$html = preg_replace('/<img[^>]+>/', '<img src="[logo]">', $html);
$start = strpos($html, '<div class="email-wrap">');
$end = strpos($html, '<div class="email-wrap">', $start + 1);
if ($end === false) {
    $end = strpos($html, '</div><!-- end email -->', $start);
}
if ($end === false) {
    $end = $start + 15000;
}
$chunk = substr($html, $start, $end - $start);
$lines = explode("\n", $chunk);
foreach ($lines as $i => $line) {
    if (strlen($line) > 300) {
        $line = substr($line, 0, 120).'...[truncated]';
    }
    echo ($i + 1).': '.$line."\n";
}
