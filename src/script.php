<?php

require_once 'config.php';
require_once '../vendor/autoload.php';

use Persistence\MemoryPersistence;
use Repository\DiscountRepository;
use Repository\OperationRepository;
use Repository\UserRepository;
use Service\CommissionCalculationService;
use Service\Currency\ExchangeService;
use Service\Currency\RatesService;

$memoryPersistence = new MemoryPersistence();

$userRepository = new UserRepository($memoryPersistence);
$operationRepository = new OperationRepository($memoryPersistence);
$discountRepository = new DiscountRepository($memoryPersistence);

$ratesService = new RatesService();
$exchangeService = new ExchangeService($ratesService, DEFAULT_CURRENCY);

$csvRows = array_map('str_getcsv', file($argv[1]));

foreach ($csvRows as $index => $csvRow) {
    $user = $userRepository->findOrCreate((int)$csvRow[1], $csvRow[2]);
    $operation = $operationRepository->create(
        $index + 1,
        $csvRow[0],
        $csvRow[3],
        $csvRow[4],
        $csvRow[5],
        $user,
        $discountRepository
    );
}

$commissionCalculatorService = new CommissionCalculationService(
    $operationRepository,
    $discountRepository,
    $exchangeService
);

$operations = $operationRepository->getAll();

foreach ($operations as $operation) {
    $commissionCalculatorService->calculate($operation);

    echo $commissionCalculatorService->getFormattedCommission($operation) . PHP_EOL;
}
