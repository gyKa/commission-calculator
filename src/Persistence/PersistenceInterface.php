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
    public function find(string $location, int $id) :? EntityInterface;

    /**
     * @param string $location
     * @return array
     */
    public function findAll(string $location) : array;

    /**
     * @param string $location
     * @param EntityInterface $entity
     * @param int|null $id
     */
    public function save(string $location, EntityInterface $entity, int $id = null) : void;

    /**
     * @param int $id
     * @param string $location
     * @return bool
     */
    public function remove(string $location, int $id) : bool;
}
