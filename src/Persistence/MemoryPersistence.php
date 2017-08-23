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
    public function find(int $id, string $location) :? EntityInterface
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
     * @param int $id
     * @param string $location
     * @param EntityInterface $entity
     */
    public function save(int $id, string $location, EntityInterface $entity) : void
    {
        $this->data[$location][$id] = $entity;
    }

    /**
     * @param int $id
     * @param string $location
     * @return bool
     */
    public function remove(int $id, string $location) : bool
    {
        if ($this->data[$location][$id]) {
            unset($this->data[$location][$id]);

            return true;
        }

        return false;
    }
}
