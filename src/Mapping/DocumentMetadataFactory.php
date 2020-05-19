<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping;

use Doctrine\KeyValueStore\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;

/**
 * @method DocumentMetadata getMetadataFor(string $className)
 */
class DocumentMetadataFactory extends ClassMetadataFactory
{
    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->initialized = true;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeReflection(ClassMetadata $class, ReflectionService $reflService)
    {
        $this->wakeupReflection($class, $reflService);
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new DocumentMetadata($className);
    }
}
