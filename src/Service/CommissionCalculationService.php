<?php

namespace Service;

use Entity\AbstractOperation;
use Entity\Discount;
use Repository\DiscountRepository;
use Repository\OperationRepository;
use Service\Currency\ExchangeService;

class CommissionCalculationService
{
    const MAXIMUM_CASH_IN_COMMISSION_AMOUNT = 500;
    const MINIMUM_CASH_OUT_COMMISSION_AMOUNT = 50;

    const OPERATION_CASH_OUT_COMMISSION_PERCENTAGE = 0.3;
    const OPERATION_CASH_IN_COMMISSION_PERCENTAGE = 0.03;

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

    /**
     * @var int
     */
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
    public function calculate(AbstractOperation $operation) : void
    {
        $this->calculateForCashIn($operation);
        $this->calculateForCashOut($operation);
    }

    /**
     * @param AbstractOperation $operation
     * @return string
     */
    public function getFormattedCommission(AbstractOperation $operation) : string
    {
        return number_format(
            $this->commission / 100,
            $operation->getAmountPrecise(),
            '.',
            ''
        );
    }

    /**
     * @param AbstractOperation $operation
     */
    protected function calculateForCashIn(AbstractOperation $operation) : void
    {
        if (!$operation->isCashInOperation()) {
            return;
        }

        $commission = $operation->getAmount() / 100 * self::OPERATION_CASH_IN_COMMISSION_PERCENTAGE;
        $commissionInEur = $this->exchangeService->calculateRate($commission, DEFAULT_CURRENCY);

        if ($commissionInEur > self::MAXIMUM_CASH_IN_COMMISSION_AMOUNT) {
            $commission = $this->exchangeService->calculateRate(
                self::MAXIMUM_CASH_IN_COMMISSION_AMOUNT,
                $operation->getCurrency()
            );
        }

        $this->commission = $commission;
    }

    /**
     * @param AbstractOperation $operation
     */
    protected function calculateForCashOut(AbstractOperation $operation) : void
    {
        if (!$operation->isCashOutOperation()) {
            return;
        }

        $this->calculateForCashOutLegalUser($operation);
        $this->calculateForCashOutNaturalUser($operation);
    }

    /**
     * @param AbstractOperation $operation
     */
    protected function calculateForCashOutLegalUser(AbstractOperation $operation) : void
    {
        if (!($operation->getUser())->isLegalUser()) {
            return;
        }

        $commission = $operation->getAmount() / 100 * self::OPERATION_CASH_OUT_COMMISSION_PERCENTAGE;
        $commissionInEur = $this->exchangeService->calculateRate($commission, DEFAULT_CURRENCY);

        if ($commissionInEur <= self::MINIMUM_CASH_OUT_COMMISSION_AMOUNT) {
            $commission = $this->exchangeService->calculateRate(
                self::MINIMUM_CASH_OUT_COMMISSION_AMOUNT,
                $operation->getCurrency()
            );
        }

        $this->commission = $commission;
    }

    /**
     * @param AbstractOperation $operation
     */
    protected function calculateForCashOutNaturalUser(AbstractOperation $operation) : void
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

    /**
     * @param $value
     * @param int $precision
     * @return float
     */
    private function ceiling($value, int $precision = 0) : float
    {
        return ceil($value * pow(10, $precision)) / pow(10, $precision);
    }

    /**
     * @param array $weekOperations
     * @param AbstractOperation $operation
     */
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

    /**
     * @param array $weekOperations
     * @param AbstractOperation $operation
     */
    private function maybeApplyRegularCommission(array $weekOperations, AbstractOperation $operation) : void
    {
        if (count($weekOperations) <= 3) {
            return;
        }

        $comm = $this->exchangeService->calculateRate(
            $operation->getAmount() / 100,
            $operation->getCurrency()
        );

        $this->commission = $comm * self::OPERATION_CASH_OUT_COMMISSION_PERCENTAGE;
    }

    /**
     * @param Discount $discount
     * @param AbstractOperation $operation
     */
    private function maybeUserHasDiscount(Discount $discount, AbstractOperation $operation) : void
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

            $this->commission = $comm * self::OPERATION_CASH_OUT_COMMISSION_PERCENTAGE;
        }
    }

    /**
     * @param Discount $discount
     * @param AbstractOperation $operation
     */
    private function maybeUserHasNotDiscount(Discount $discount, AbstractOperation $operation) : void
    {
        if (!is_null($discount)) {
            return;
        }

        $this->commission = $this->exchangeService->calculateRate(
            $operation->getAmount() / 100,
            $operation->getCurrency(),
            DEFAULT_CURRENCY
        ) * self::OPERATION_CASH_OUT_COMMISSION_PERCENTAGE;
    }
}
