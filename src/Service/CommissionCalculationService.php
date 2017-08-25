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
        return number_format(
            $this->commission / 100,
            2
        );
    }

    protected function calculateForCashIn(AbstractOperation $operation)
    {
        if (!$operation->isCashInOperation()) {
            return;
        }

        $commission = $operation->getAmount() / 100 * 0.03;
        $commissionInEur = $this->exchangeService->calculateRate($commission, DEFAULT_CURRENCY);

        $this->commission = $commissionInEur > 500 ? $this->exchangeService->calculateRate(500, $operation->getCurrency()) : $commission;
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

        $weekOperations = $this->operationRepository->getWeekOperations(
            $operation->getDate(),
            $operation->getUser()->getId(),
            $operation->getId()
        );

        if (count($weekOperations) <= 3) {
            $discount = $this->discountRepository->find(
                $operation->getUser()->getId(),
                $operation->getDate()
            );

            if (!is_null($discount)) {
                $convertedAmountFloat = $this->exchangeService->calculateRate(
                    $operation->getAmount() / 100,
                    DEFAULT_CURRENCY,
                    $operation->getCurrency()
                );

                $convertedAmountInt = $this->ceiling($convertedAmountFloat, 2) * 100;
                $unusedAmount = $discount->useDiscount($convertedAmountInt);

                if ($unusedAmount === 0) {
                    $this->commission = 0;
                    return;
                }

                if ($unusedAmount > 0) {
                    $comm = $this->exchangeService->calculateRate(
                        $unusedAmount / 100,
                        $operation->getCurrency(),
                        DEFAULT_CURRENCY
                    );

                    $this->commission = $comm * 0.3;
                    return;
                }
            }
        } else {
            $comm = $this->exchangeService->calculateRate(
                $operation->getAmount() / 100,
                $operation->getCurrency()
            );

            $this->commission = $comm * 0.3;
            return;
        }
    }

    private function ceiling($value, $precision = 0) {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }
}
