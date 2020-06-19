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
     * @param DocumentMetadata $class
     * @param DocumentMetadata|null $parent
     * @param bool $rootEntityFound
     * @param string[] $nonSuperclassParents
     * @throws \InvalidArgumentException
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        try {
            parent::doLoadMetadata($class, $parent, $rootEntityFound, $nonSuperclassParents);
        } catch (\InvalidArgumentException $e) {
            if ($e->getMessage() != "Class {$class->name} has no identifier.") {
                throw $e;
            }
            if (!$class->embedded && !$class->identifier) {
                throw new \InvalidArgumentException("Document '{$class->name}' must have identifier.");
            }
        }
        if ($class->embedded && $class->identifier) {
            throw new \InvalidArgumentException("Embedded document '{$class->name}' cannot have identifier.");
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new DocumentMetadata($className);
    }
}
