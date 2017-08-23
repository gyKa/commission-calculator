<?php

namespace Persistence;

use Entity\EntityInterface;

interface PersistenceInterface
{
    /**
     * @param int $id
     * @param string $location
     * @return EntityInterface|null
     */
    public function find(int $id, string $location) :? EntityInterface;

    /**
     * @param int $id
     * @param string $location
     * @param EntityInterface $entity
     */
    public function save(int $id, string $location, EntityInterface $entity) : void;

    /**
     * @param int $id
     * @param string $location
     * @return bool
     */
    public function remove(int $id, string $location) : bool;
}
