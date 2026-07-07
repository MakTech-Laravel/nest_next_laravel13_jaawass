<?php

$html = file_get_contents(__DIR__.'/../resources/views/demo/templates/b1-welcome.html');
$start = strpos($html, '<div class="email-wrap">');
$chunk = substr($html, $start, 30000);
$chunk = preg_replace('/data:image\/[^"]+"/', 'data:image/..."', $chunk);
echo $chunk;
