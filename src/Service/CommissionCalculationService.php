<?php

namespace Service;

use Entity\AbstractOperation;
use Entity\Discount;
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

        if ($commissionInEur > 500) {
            $commission = $this->exchangeService->calculateRate(500, $operation->getCurrency());
        }

        $this->commission = $commission;
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

        if ($commissionInEur <= 50) {
            $commission = $this->exchangeService->calculateRate(500, $operation->getCurrency());
        }

        $this->commission = $commission;
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

        $this->maybeApplyDiscount($weekOperations, $operation);
        $this->maybeApplyRegularCommission($weekOperations, $operation);
    }

    private function ceiling($value, $precision = 0)
    {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }

    private function maybeApplyDiscount(array $weekOperations, AbstractOperation $operation) : void
    {
        if (count($weekOperations) >= 4) {
            return;
        }

        $discount = $this->discountRepository->find(
            $operation->getUser()->getId(),
            $operation->getDate()
        );

        $this->maybeUserHasDiscount($discount, $operation);
        $this->maybeUserHasNotDiscount($discount, $operation);
    }

    private function maybeApplyRegularCommission(array $weekOperations, AbstractOperation $operation) : void
    {
        if (count($weekOperations) <= 3) {
            return;
        }

        $comm = $this->exchangeService->calculateRate(
            $operation->getAmount() / 100,
            $operation->getCurrency()
        );

        $this->commission = $comm * 0.3;
    }

    private function maybeUserHasDiscount(Discount $discount, AbstractOperation $operation)
    {
        if (is_null($discount)) {
            return;
        }

        $convertedAmountFloat = $this->exchangeService->calculateRate(
            $operation->getAmount() / 100,
            DEFAULT_CURRENCY,
            $operation->getCurrency()
        );

        $convertedAmountInt = $this->ceiling($convertedAmountFloat, 2) * 100;
        $unusedAmount = $discount->useDiscount($convertedAmountInt);

        if ($unusedAmount === 0) {
            $this->commission = 0;
        }

        if ($unusedAmount > 0) {
            $comm = $this->exchangeService->calculateRate(
                $unusedAmount / 100,
                $operation->getCurrency(),
                DEFAULT_CURRENCY
            );

            $this->commission = $comm * 0.3;
        }
    }

    private function maybeUserHasNotDiscount(Discount $discount, AbstractOperation $operation)
    {
        if (!is_null($discount)) {
            return;
        }

        $this->commission = $this->exchangeService->calculateRate(
            $operation->getAmount() / 100,
            $operation->getCurrency(),
            DEFAULT_CURRENCY
        ) * 0.3;
    }
}
