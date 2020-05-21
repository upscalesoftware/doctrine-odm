<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM;

use Doctrine\KeyValueStore\Configuration;
use Doctrine\KeyValueStore\KeyValueStoreException;
use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\KeyValueStore\Storage\Storage;
use Doctrine\Persistence\Mapping\MappingException;
use Upscale\Doctrine\ODM\Mapping\DocumentMetadataFactory;
use Upscale\Doctrine\ODM\Types\TypeManager;

class EntityManager
{
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;

    /**
     * @var Storage
     */
    private $storageDriver;

    /**
     * Inject dependencies
     *
     * @param Storage $storageDriver
     * @param Configuration $config
     * @param TypeManager|null $typeManager
     * @throws KeyValueStoreException
     */
    public function __construct(Storage $storageDriver, Configuration $config, TypeManager $typeManager = null)
    {
        if (!$typeManager) {
            $typeManager = new TypeManager([
                'boolean'   => new Types\BooleanType(),
                'date'      => new Types\DateTimeType(Types\DateTimeType::DATE),
                'time'      => new Types\DateTimeType(Types\DateTimeType::TIME),
                'datetime'  => new Types\DateTimeType(),
                'float'     => new Types\FloatType(),
                'integer'   => new Types\IntegerType(),
                'mixed'     => new Types\MixedType(),
                'string'    => new Types\StringType(),
            ]);
        }
        
        $metadataFactory = new DocumentMetadataFactory($config->getMappingDriverImpl());
        $metadataFactory->setCacheDriver($config->getMetadataCache());

        $this->unitOfWork = new UnitOfWork($metadataFactory, $storageDriver, $typeManager, $config);
        $this->storageDriver = $storageDriver;
    }

    /**
     * Fetch an object from persistent storage by its unique identifier
     *
     * @param string $className
     * @param string|array $key
     * @return object
     * @throws MappingException
     * @throws NotFoundException
     * @throws \ReflectionException
     */
    public function find(string $className, $key)
    {
        return $this->unitOfWork->reconstitute($className, $key);
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
     */
    public function clear()
    {
        $this->unitOfWork->clear();
    }
}
