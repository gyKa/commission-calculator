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
     * @return AbstractOperation
     */
    public function create(
        int $id,
        string $date,
        string $operationType,
        string $amount,
        string $currency,
        AbstractUser $user
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
        }

        $operation->setId($id);
        $operation->setDate(\DateTime::createFromFormat(DATE_FORMAT, $date));
        $operation->setAmount($amount);
        $operation->setCurrency($currency);
        $operation->setUser($user);

        $this->persistence->save('operation', $operation);

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
     * @return array
     */
    public function getWeekOperations(\DateTime $date) : array
    {
        $result = [];
        $currentWeek = $date->format('W');

        $operations = $this->persistence->findAll('operation');

        /**
         * @var AbstractOperation $operation
         */
        foreach ($operations as $operation) {
            $operationWeek = $operation->getDate()->format('W');

            if ($operationWeek === $currentWeek) {
                $result[] = $operation;
            }
        }

        return $result;
    }
}
