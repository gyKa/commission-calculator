<?php

namespace Service;

use Entity\AbstractOperation;
use Repository\DiscountRepository;
use Repository\OperationRepository;
use Service\Currency\ExchangeService;

class CommissionCalculationService
{
    /**
     * @var OperationRepository
     */
    protected $operationRepository;

    /**
     * @var DiscountRepository
     */
    protected $discountRepository;

    /**
     * @var ExchangeService
     */
    protected $exchangeService;

    private $commission;

    /**
     * @param OperationRepository $operationRepository
     * @param DiscountRepository $discountRepository
     * @param ExchangeService $exchangeService
     */
    public function __construct(
        OperationRepository $operationRepository,
        DiscountRepository $discountRepository,
        ExchangeService $exchangeService
    ) {
        $this->discountRepository = $discountRepository;
        $this->operationRepository = $operationRepository;
        $this->exchangeService = $exchangeService;
    }

    /**
     * @param AbstractOperation $operation
     */
    public function calculate(AbstractOperation $operation)
    {
        $this->calculateForCashIn($operation);
        $this->calculateForCashOut($operation);
    }

    public function getFormattedCommission()
    {
//        return $this->commission % 100 === 0 ? $this->commission / 100 : number_format($this->commission / 100, 2, '.');
        return $this->commission;
    }

    protected function calculateForCashIn(AbstractOperation $operation)
    {
        if (!$operation->isCashInOperation()) {
            return;
        }

        $commission = $operation->getAmount() / 100 * 0.03;
        $commissionInEur = $this->exchangeService->calculateRate($commission, DEFAULT_CURRENCY);

        $this->commission = $commissionInEur > 500 ? $this->exchangeService->calculateRate(500, $operation->getCurrency()) : $commission;

//        echo sprintf(
//            "Commission: %s | CommissionEUR: %s | Amount: %s | Currency: %s | Data: %s \n",
//            $commission,
//            $commissionInEur,
//            $operation->getAmount(),
//            $operation->getCurrency(),
//            $operation->getDate()->format(DATE_FORMAT)
//        );
    }

    protected function calculateForCashOut(AbstractOperation $operation)
    {
        if (!$operation->isCashOutOperation()) {
            return;
        }

        $this->calculateForCashOutLegalUser($operation);
        $this->calculateForCashOutNaturalUser($operation);
    }

    protected function calculateForCashOutLegalUser(AbstractOperation $operation)
    {
        if (!($operation->getUser())->isLegalUser()) {
            return;
        }

        $commission = $operation->getAmount() / 100 * 0.3;
        $commissionInEur = $this->exchangeService->calculateRate($commission, DEFAULT_CURRENCY);

        $this->commission = $commissionInEur <= 50 ? $this->exchangeService->calculateRate(500, $operation->getCurrency()) : $commission;

    }

    protected function calculateForCashOutNaturalUser(AbstractOperation $operation)
    {
        if (!($operation->getUser())->isNaturalUser()) {
            return;
        }

        $commission = 0;

        $weekOperations = $this->operationRepository->getWeekOperations(
            $operation->getDate(),
            $operation->getUser()->getId(),
            $operation->getId()
        );
        $weekAmountEur = $this->getOperationsAmountEur($weekOperations);

        if ($weekAmountEur > 100000) {
            /**
             * @var AbstractOperation[] $discountOperations
             */
            $discountOperations = array_slice($weekOperations, 0, 3);

            $discountAmountEur = $this->getOperationsAmountEur($discountOperations);

            if ($discountAmountEur > 100000) {
                $discountAmountDiffEur = $discountAmountEur - 100000;
                $commission = ceil($discountAmountDiffEur / 100 * 0.3);
            }

            if (count($weekOperations) >= 4) {
                $otherOperations = array_slice($weekOperations, 3, count($weekOperations) - 3);

                /**
                 * @var AbstractOperation $otherOperation
                 */
                foreach ($otherOperations as $otherOperation) {
                    $commission += $otherOperation->getAmount() / 100 * 0.3;
                }
            }
        }

        $this->commission = $commission;
    }

    private function getOperationsAmountEur(array $operations) : float
    {
        $amount = 0;

        /**
         * @var AbstractOperation $operation
         */
        foreach ($operations as $operation) {
            $convertedAmount = $this->exchangeService->calculateRate(
                $operation->getAmount() / 100,
                DEFAULT_CURRENCY,
                $operation->getCurrency()
            );

            $amount += $this->ceiling($convertedAmount, 2);
        }

        return $amount * 100;
    }

    private function ceiling($value, $precision = 0) {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }
}
