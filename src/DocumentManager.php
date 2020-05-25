<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM;

use Doctrine\KeyValueStore\KeyValueStoreException;
use Doctrine\KeyValueStore\Storage\Storage;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\ObjectManager;
use Upscale\Doctrine\ODM\Mapping\DocumentMetadata;
use Upscale\Doctrine\ODM\Mapping\DocumentMetadataFactory;

class DocumentManager implements ObjectManager
{
    /**
     * @var DocumentMetadataFactory
     */
    private $metadataFactory;
    
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var Storage
     */
    private $storageDriver;

    /**
     * @var DocumentRepository[]
     */
    private $repositories = [];

    /**
     * Inject dependencies
     *
     * @param Storage $storageDriver
     * @param Configuration $config
     * @throws KeyValueStoreException
     */
    public function __construct(Storage $storageDriver, Configuration $config)
    {
        $this->metadataFactory = new DocumentMetadataFactory($config->getMappingDriverImpl());
        $this->metadataFactory->setCacheDriver($config->getMetadataCache());

        $this->unitOfWork = new UnitOfWork($this->metadataFactory, $storageDriver, $config->getTypeManager(), $config);
        $this->storageDriver = $storageDriver;
    }

    /**
     * @return UnitOfWork
     */
    public function getUnitOfWork()
    {
        return $this->unitOfWork;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        return $this->metadataFactory;
    }

    /**
     * @param string $className
     * @return DocumentMetadata
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function getClassMetadata($className)
    {
        return $this->metadataFactory->getMetadataFor($className);
    }

    /**
     * @param string $className
     * @return DocumentRepository
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function getRepository($className)
    {
        if (!isset($this->repositories[$className])) {
            $class = $this->getClassMetadata($className);
            $this->repositories[$className] = new DocumentRepository($this->unitOfWork, $class);
        }
        return $this->repositories[$className];
    }

    /**
     * Fetch an object from persistent storage by its unique identifier
     *
     * @param string $className
     * @param string|array $id
     * @return object
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function find($className, $id)
    {
        return $this->getRepository($className)->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($object)
    {
        return $this->unitOfWork->contains($object);
    }

    /**
     * Schedule a new object for saving in persistent storage
     * 
     * @param object $object
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function persist($object)
    {
        $this->unitOfWork->scheduleForInsert($object);
    }

    /**
     * Schedule an object for removal from persistent storage
     *
     * @param object $object
     */
    public function remove($object)
    {
        $this->unitOfWork->scheduleForDelete($object);
    }

    /**
     * Flush all pending changes to persistent storage
     * 
     * @throws MappingException
     * @throws \ReflectionException
     */
    public function flush()
    {
        $this->unitOfWork->commit();
    }

    /**
     * Clear all pending changes to persistent storage
     * 
     * @param string|null $objectName
     */
    public function clear($objectName = null)
    {
        if ($objectName !== null) {
            throw new \BadMethodCallException('Partial cleaning is not supported');
        }
        $this->unitOfWork->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function merge($object)
    {
        throw new \BadMethodCallException('Merging is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function detach($object)
    {
        throw new \BadMethodCallException('Detaching is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function refresh($object)
    {
        throw new \BadMethodCallException('Refreshing is not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function initializeObject($obj)
    {
        // Do nothing
    }
}
