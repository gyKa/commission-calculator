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
     * @var int
     */
    protected $id;

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
     * @var AbstractUser
     */
    protected $user;

    /**
     * @var int
     */
    protected $amountPrecise;

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
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
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

    /**
     * @return AbstractUser
     */
    public function getUser(): AbstractUser
    {
        return $this->user;
    }

    /**
     * @param AbstractUser $user
     */
    public function setUser(AbstractUser $user)
    {
        $this->user = $user;
    }

    /**
     * @return int
     */
    public function getAmountPrecise(): int
    {
        return $this->amountPrecise;
    }

    /**
     * @param int $amountPrecise
     */
    public function setAmountPrecise(int $amountPrecise)
    {
        $this->amountPrecise = $amountPrecise;
    }
}
