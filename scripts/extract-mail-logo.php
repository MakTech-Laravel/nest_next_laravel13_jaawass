<?php

$html = file_get_contents(__DIR__.'/../resources/views/demo/templates/b1-welcome.html');

if (! preg_match('/<div class="email-wrap">.*?<img src="(data:image\/png;base64,[^"]+)"/s', $html, $matches)) {
    fwrite(STDERR, "Logo not found\n");
    exit(1);
}

$dir = __DIR__.'/../public/images/mail';
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$data = base64_decode(substr($matches[1], strlen('data:image/png;base64,')));
$out = $dir.'/sourcenest-logo.png';
file_put_contents($out, $data);

echo "Saved {$out} (".strlen($data)." bytes)\n";
