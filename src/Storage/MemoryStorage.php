<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Storage;

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Storage\Storage;

class MemoryStorage implements Storage, \JsonSerializable
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * Inject dependencies
     * 
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsPartialUpdates()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresCompositePrimaryKeys()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function insert($storageName, $key, array $data)
    {
        $this->update($storageName, $key, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function update($storageName, $key, array $data)
    {
        $this->data[$storageName][$key] = $data;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($storageName, $key)
    {
        unset($this->data[$storageName][$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function find($storageName, $key)
    {
        if (!isset($this->data[$storageName][$key])) {
            throw new NotFoundException();
        }
        return $this->data[$storageName][$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'memory';
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}