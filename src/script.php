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
$commissionCalculatorService = new CommissionCalculationService(
    $operationRepository,
    $discountRepository,
    $exchangeService
);

$csvRows = array_map('str_getcsv', file($argv[1]));

// Set up and store operation and user entities.
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

$operations = $operationRepository->getAll();

// Calculate commission for operations.
foreach ($operations as $operation) {
    $commissionCalculatorService->calculate($operation);

    fwrite(STDOUT, $commissionCalculatorService->getFormattedCommission($operation) . PHP_EOL);
}
