<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM;

use Doctrine\KeyValueStore\Configuration;
use Doctrine\KeyValueStore\Id\CompositeIdHandler;
use Doctrine\KeyValueStore\Id\IdConverterStrategy;
use Doctrine\KeyValueStore\Id\IdHandlingStrategy;
use Doctrine\KeyValueStore\Id\SingleIdHandler;
use Doctrine\KeyValueStore\Mapping\ClassMetadata;
use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Storage\Storage;
use Doctrine\Persistence\Mapping\MappingException;
use Upscale\Doctrine\ODM\Mapping\DocumentMetadataFactory;

class UnitOfWork
{
    /**
     * @var DocumentMetadataFactory
     */
    private $metadataFactory;
    
    /**
     * @var Storage
     */
    private $storageDriver;

    /**
     * @var IdConverterStrategy
     */
    private $idConverter;

    /**
     * @var IdHandlingStrategy
     */
    private $idHandler;

    /**
     * @var array
     */
    private $identifiers = [];

    /**
     * @var array
     */
    private $originalData = [];

    /**
     * @var array
     */
    private $scheduledInsertions = [];

    /**
     * @var array
     */
    private $scheduledDeletions = [];
    
    /**
     * @var array 
     */
    private $identityMap = [];

    /**
     * Inject dependencies
     * 
     * @param DocumentMetadataFactory $metadataFactory
     * @param Storage $storageDriver
     * @param Configuration $config
     */
    public function __construct(
        DocumentMetadataFactory $metadataFactory,
        Storage $storageDriver,
        Configuration $config
    ) {
        $this->metadataFactory = $metadataFactory;
        $this->storageDriver = $storageDriver;
        $this->idConverter = $config->getIdConverterStrategy();
        $this->idHandler = $storageDriver->supportsCompositePrimaryKeys()
            ? new CompositeIdHandler()
            : new SingleIdHandler();
    }

    /**
     * @param string $className
     * @param string|array $key
     * @return object
     * @throws MappingException
     * @throws NotFoundException
     * @throws \ReflectionException
     */
    public function reconstitute(string $className, $key)
    {
        $class = $this->metadataFactory->getMetadataFor($className);
        $id = $this->idHandler->normalizeId($class, $key);
        $data = $this->storageDriver->find($class->storageName, $id);
        return $this->createEntity($class, $id, $data);
    }

    /**
     * @param ClassMetadata $class
     * @param string|array $id
     * @param array $data
     * @return object
     */
    public function createEntity(ClassMetadata $class, $id, array $data)
    {
        $idHash = $this->idHandler->hash($id);
        if (isset($this->identityMap[$class->name][$idHash])) {
            return $this->identityMap[$class->name][$idHash];
        }

        $object = $class->newInstance();

        $oid = spl_object_hash($object);
        $this->originalData[$oid] = $data;
        $data = $this->idConverter->unserialize($class, $data);

        foreach ($data as $fieldName => $value) {
            if (isset($class->reflFields[$fieldName])) {
                $class->reflFields[$fieldName]->setValue($object, $value);
            }
        }

        $this->identityMap[$class->name][$idHash] = $object;
        $this->identifiers[$oid] = $id;

        return $object;
    }

    /**
     * @param ClassMetadata $class
     * @param object $object
     * @return array
     */
    private function computeChangeSet(ClassMetadata $class, $object): array
    {
        $result = [];
        $snapshot = $this->getObjectSnapshot($class, $object);
        $originalData = $this->originalData[spl_object_hash($object)];
        foreach ($snapshot as $fieldName => $value) {
            if (!isset($originalData[$fieldName]) || $originalData[$fieldName] !== $value) {
                $result[$fieldName] = $value;
            }
        }
        if ($result && !$this->storageDriver->supportsPartialUpdates()) {
            $result = array_merge($originalData, $result);
        }
        return $result;
    }

    /**
     * @param ClassMetadata $class
     * @param object $object
     * @return array
     */
    private function getObjectSnapshot(ClassMetadata $class, $object)
    {
        $result = [];
        foreach ($class->reflFields as $fieldName => $reflProperty) {
            $result[$fieldName] = $reflProperty->getValue($object);
        }
        return $result;
    }

    /**
     * @param object $object
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function scheduleForInsert($object)
    {
        $oid = spl_object_hash($object);
        if (isset($this->identifiers[$oid])) {
            return;
        }

        $class = $this->metadataFactory->getMetadataFor(get_class($object));
        $id = $this->idHandler->getIdentifier($class, $object);
        if (!$id) {
            throw new \RuntimeException('Cannot persist document without identifier.');
        }

        $idHash = $this->idHandler->hash($id);

        if (isset($this->identityMap[$class->name][$idHash])) {
            throw new \RuntimeException('Document with the same identifier already exists.');
        }

        $this->scheduledInsertions[$oid] = $object;
        $this->identityMap[$class->name][$idHash] = $object;
    }

    /**
     * @param object $object
     * @throws \RuntimeException
     */
    public function scheduleForDelete($object)
    {
        $oid = spl_object_hash($object);
        if (!isset($this->identifiers[$oid])) {
            throw new \RuntimeException(
                'Object scheduled for deletion is not managed. Only managed objects can be deleted.'
            );
        }
        $this->scheduledDeletions[$oid] = $object;
    }

    /**
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function processUpdates()
    {
        foreach ($this->identityMap as $className => $objects) {
            foreach ($objects as $object) {
                $hash = spl_object_hash($object);

                if (isset($this->scheduledInsertions[$hash])) {
                    continue;
                }

                $metadata = $this->metadataFactory->getMetadataFor($className);
                $changeSet = $this->computeChangeSet($metadata, $object);

                if ($changeSet) {
                    $this->storageDriver->update($metadata->storageName, $this->identifiers[$hash], $changeSet);

                    if ($this->storageDriver->supportsPartialUpdates()) {
                        $this->originalData[$hash] = array_merge($this->originalData[$hash], $changeSet);
                    } else {
                        $this->originalData[$hash] = $changeSet;
                    }
                }
            }
        }
    }

    /**
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function processInsertions()
    {
        foreach ($this->scheduledInsertions as $object) {
            $class = $this->metadataFactory->getMetadataFor(get_class($object));
            $id = $this->idHandler->getIdentifier($class, $object);
            $id = $this->idConverter->serialize($class, $id);

            if (!$id) {
                throw new \RuntimeException('Cannot persist document without identifier.');
            }

            $data = $this->getObjectSnapshot($class, $object);

            $oid = spl_object_hash($object);
            $idHash = $this->idHandler->hash($id);

            $this->storageDriver->insert($class->storageName, $id, $data);

            $this->originalData[$oid] = $data;
            $this->identifiers[$oid] = $id;
            $this->identityMap[$class->name][$idHash] = $object;
        }
    }

    /**
     * @throws MappingException
     * @throws \ReflectionException
     */
    private function processDeletions()
    {
        foreach ($this->scheduledDeletions as $object) {
            $class = $this->metadataFactory->getMetadataFor(get_class($object));
            $oid = spl_object_hash($object);
            $id = $this->identifiers[$oid];
            $idHash = $this->idHandler->hash($id);

            $this->storageDriver->delete($class->storageName, $id);

            unset($this->identifiers[$oid], $this->originalData[$oid], $this->identityMap[$class->name][$idHash]);
        }
    }

    /**
     * Commit pending changes to persistent storage
     * 
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function commit()
    {
        $this->processUpdates();
        $this->processInsertions();
        $this->processDeletions();

        $this->scheduledInsertions = [];
        $this->scheduledDeletions  = [];
    }

    /**
     * Clear pending changes and in-memory caches
     */
    public function clear()
    {
        $this->scheduledInsertions = [];
        $this->scheduledDeletions = [];
        $this->identifiers = [];
        $this->originalData = [];
        $this->identityMap = [];
    }
}
