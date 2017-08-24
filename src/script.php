<?php

require_once 'config.php';
require_once '../vendor/autoload.php';

use Persistence\MemoryPersistence;
use Repository\OperationRepository;
use Repository\UserRepository;
use Service\Currency\ExchangeService;
use Service\Currency\RatesService;

$memoryPersistence = new MemoryPersistence();

$userRepository = new UserRepository($memoryPersistence);
$operationRepository = new OperationRepository($memoryPersistence);

$ratesService = new RatesService();
$exchangeService = new ExchangeService($ratesService, DEFAULT_CURRENCY);

$csvRows = array_map('str_getcsv', file($argv[1]));

foreach ($csvRows as $csvRow) {
    $user = $userRepository->findOrCreate((int)$csvRow[1], $csvRow[2]);
    $operation = $operationRepository->create($csvRow[0], $csvRow[3], $csvRow[4], $csvRow[5]);
}

$exchangeService->calculateRate(10, 'USD');
