<?php

namespace Entity;

class AbstractOperation implements EntityInterface
{
    /**
     * @var bool
     */
    protected $isCashOutOperation = false;

    /**
     * @var bool
     */
    protected $isCashInOperation = false;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var int
     */
    protected $amount;

    /**
     * @var string
     */
    protected $currency;

    /**
     * @return bool
     */
    public function isCashOutOperation() : bool
    {
        return $this->isCashOutOperation;
    }

    /**
     * @return bool
     */
    public function isCashInOperation() : bool
    {
        return $this->isCashInOperation;
    }

    /**
     * @return \DateTime
     */
    public function getDate() : \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate(\DateTime $date)
    {
        $this->date = $date;
    }

    /**
     * @return int
     */
    public function getAmount() : int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getCurrency() : string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency)
    {
        $this->currency = $currency;
    }
}
