<?php

require_once 'config.php';
require_once '../vendor/autoload.php';

use Service\Currency\ExchangeService;
use Service\Currency\RatesService;

$ratesService = new RatesService();
$exchangeService = new ExchangeService($ratesService, DEFAULT_CURRENCY);

$exchangeService->calculateRate(10, 'USD');
