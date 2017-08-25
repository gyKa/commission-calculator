<?php

namespace Entity;

abstract class AbstractUser implements EntityInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var bool
     */
    protected $isLegalUser = false;

    /**
     * @var bool
     */
    protected $isNaturalUser = false;

    /**
     * @return int
     */
    public function getId() : int
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
     * @return bool
     */
    public function isLegalUser() : bool
    {
        return $this->isLegalUser;
    }

    /**
     * @return bool
     */
    public function isNaturalUser() : bool
    {
        return $this->isNaturalUser;
    }
}
