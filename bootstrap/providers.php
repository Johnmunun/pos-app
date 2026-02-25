<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\DomainServiceProvider::class,
    Src\Infrastructure\Pharmacy\Providers\PharmacyServiceProvider::class,
    Src\Infrastructure\Finance\Providers\FinanceServiceProvider::class,
    Src\Infrastructure\Search\Providers\SearchServiceProvider::class,
    Src\Infrastructure\Settings\Providers\SettingsServiceProvider::class,
    Src\Infrastructure\Currency\Providers\CurrencyServiceProvider::class,
];
