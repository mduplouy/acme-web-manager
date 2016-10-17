<?php

namespace AcmeWebManager;

use Octopuce\Acme\Storage\StorageInterface;

interface CollectionableStorageInterface extends StorageInterface
{
    /**
     * Returns an array of all storable elements
     *
     * @return array
     */
    public function findAll();
}
