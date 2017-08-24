<?php

namespace Repository;

use Entity\AbstractOperation;
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
     * @param string $date
     * @param string $operationType
     * @param string $amount
     * @param string $currency
     * @return AbstractOperation
     */
    public function create(string $date, string $operationType, string $amount, string $currency) : AbstractOperation
    {
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

        $operation->setDate(\DateTime::createFromFormat('Y-m-d', $date));
        $operation->setAmount($amount);
        $operation->setCurrency($currency);

        $this->persistence->save('operation', $operation);

        return $operation;
    }
}
