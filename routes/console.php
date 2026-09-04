<?php

use App\Console\Commands\QueryBinance;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:queryBinance {--fiat=VES} {--side=BUY} {--rows=10}', function () {
    $this->call(QueryBinance::class, [
        '--fiat' => $this->option('fiat'),
        '--side' => $this->option('side'),
        '--rows' => $this->option('rows'),
    ]);
})->purpose('Query the Binance P2P USDT market for the best offers.')->everyThirtyMinutes();

Artisan::command('app:queryCOP', function () {
    $this->call('App\Console\Commands\QueryCOP');
})->purpose('Query the latest COP/USD TRM and store its value in VES.')->twiceDaily(0, 12);

Artisan::command('app:queryBCV', function () {
    $this->call('App\Console\Commands\QueryBCV');
})->purpose('Query the latest USD/VES and EUR/VES rates from the BCV website.')->everyTwoHours();