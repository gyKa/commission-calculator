<?php

namespace Entity;

abstract class AbstractUser implements EntityInterface
{
    /**
     * @var bool
     */
    protected $isLegalUser = false;

    /**
     * @var bool
     */
    protected $isNaturalUser = false;

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
