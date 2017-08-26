<?php

namespace Repository;

use Entity\AbstractOperation;
use Entity\AbstractUser;
use Entity\CashInOperation;
use Entity\CashOutOperation;
use Persistence\PersistenceInterface;

class OperationRepository
{
    /**
     * @var PersistenceInterface
     */
    private $persistence;

    /**
     * @param PersistenceInterface $persistence
     */
    public function __construct(PersistenceInterface $persistence)
    {
        $this->persistence = $persistence;
    }

    /**
     * @param int $id
     * @param string $date
     * @param string $operationType
     * @param string $amount
     * @param string $currency
     * @param AbstractUser $user
     * @param DiscountRepository $discountRepository
     * @return AbstractOperation
     */
    public function create(
        int $id,
        string $date,
        string $operationType,
        string $amount,
        string $currency,
        AbstractUser $user,
        DiscountRepository $discountRepository
    ) : AbstractOperation {
        $operation = null;

        switch ($operationType) {
            case 'cash_in':
                $operation = new CashInOperation();
                break;

            case 'cash_out':
                $operation = new CashOutOperation();
                break;
        }

        if (strpos($amount, '.') !== false) {
            $amount = str_replace('.', '', $amount);
            $operation->setAmountPrecise(2);
        } else {
            $operation->setAmountPrecise(0);
            $amount *= 100;
        }

        $datetime = \DateTime::createFromFormat(DATE_FORMAT, $date);

        $operation->setId($id);
        $operation->setDate($datetime);
        $operation->setAmount($amount);
        $operation->setCurrency($currency);
        $operation->setUser($user);

        $this->persistence->save('operation', $operation);

        // Create discount.
        if ($operation->isCashOutOperation() && $user->isNaturalUser()) {
            $discountRepository->create(
                $user,
                $this->getDateWeekStart($datetime),
                $this->getDateWeekEnd($datetime),
                100000
            );
        }

        return $operation;
    }

    /**
     * @return array
     */
    public function getAll() : array
    {
        return $this->persistence->findAll('operation');
    }

    /**
     * @param \DateTime $date
     * @param int $userId
     * @param int $operationId
     * @return int
     */
    public function getWeekOperationsCounter(\DateTime $date, int $userId, int $operationId) : int
    {
        $result = 0;

        $operations = $this->persistence->findAll('operation');

        /**
         * @var AbstractOperation $operation
         */
        foreach ($operations as $operation) {
            $isInCurrentWeek = $operation->getDate() >= $this->getDateWeekStart($date) &&
                $operation->getDate() <= $this->getDateWeekEnd($date);
            $isOlderByDate = $operation->getDate() <= $date;
            $isSameUser = $operation->getUser()->getId() === $userId;
            $isOlderInTimeline = $operation->getId() <= $operationId;
            $isOlder = $isOlderByDate && $isOlderInTimeline;

            if ($isInCurrentWeek && $isOlder && $operation->isCashOutOperation() && $isSameUser) {
                $result += 1;
            }
        }

        return $result;
    }

    /**
     * @param \DateTime $date
     * @return \DateTime
     */
    public function getDateWeekStart(\DateTime $date) : \DateTime
    {
        $weekDay = (int)$date->format('w');
        $weekDay = $weekDay === 0 ? 7 : $weekDay;

        $monday = clone $date;

        return $monday->modify(
            sprintf('-%s day', $weekDay - 1)
        );
    }

    /**
     * @param \DateTime $date
     * @return \DateTime
     */
    public function getDateWeekEnd(\DateTime $date) : \DateTime
    {
        $weekDay = (int)$date->format('w');
        $weekDay = $weekDay === 0 ? 7 : $weekDay;

        $sunday = clone $date;

        return $sunday->modify(
            sprintf('+%s day', 7 - $weekDay)
        );
    }
}
