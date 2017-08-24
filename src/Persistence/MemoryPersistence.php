<?php

namespace Persistence;

use Entity\EntityInterface;

class MemoryPersistence implements PersistenceInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param int $id
     * @param string $location
     * @return EntityInterface|null
     */
    public function find(string $location, int $id) :? EntityInterface
    {
        if (!array_key_exists($location, $this->data)) {
            $this->data[$location] = [];
        }

        if (array_key_exists($id, $this->data[$location])) {
            return $this->data[$location][$id];
        }

        return null;
    }

    /**
     * @param string $location
     * @return array
     */
    public function findAll(string $location) : array
    {
        if (!array_key_exists($location, $this->data)) {
            $this->data[$location] = [];
        }

        return $this->data[$location];
    }

    /**
     * @param string $location
     * @param EntityInterface $entity
     * @param int|null $id
     */
    public function save(string $location, EntityInterface $entity, int $id = null) : void
    {
        if (is_null($id)) {
            $this->data[$location][] = $entity;
        } else {
            $this->data[$location][$id] = $entity;
        }
    }

    /**
     * @param int $id
     * @param string $location
     * @return bool
     */
    public function remove(string $location, int $id) : bool
    {
        if ($this->data[$location][$id]) {
            unset($this->data[$location][$id]);

            return true;
        }

        return false;
    }
}
