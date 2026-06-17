<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailingServiceProvider;
use App\Providers\TranslationServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MailingServiceProvider::class,
    TranslationServiceProvider::class,
];
