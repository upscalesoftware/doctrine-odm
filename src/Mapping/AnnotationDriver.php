<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class AnnotationDriver implements MappingDriver
{
    /**
     * @var AnnotationReader
     */
    private $reader;

    /**
     * Inject dependencies
     *
     * @param $reader AnnotationReader
     */
    public function __construct(AnnotationReader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * @param string $className
     * @param DocumentMetadata $metadata
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        $class = $metadata->getReflectionClass();

        $documentInfo = $this->reader->getClassAnnotation($class, Annotations\Document::class);
        if (!$documentInfo instanceof Annotations\Document) {
            throw new \InvalidArgumentException("Class '{$metadata->getName()}' is not a valid document.");
        }
        $metadata->storageName = $documentInfo->collection;

        foreach ($class->getProperties() as $property) {
            $fieldName = $property->getName();
            $mapping = ['fieldName' => $fieldName];
            foreach ($this->reader->getPropertyAnnotations($property) as $annotation) {
                if ($annotation instanceof Annotations\Id) {
                    $mapping += ['id' => true];
                    $metadata->mapIdentifier($fieldName);
                } else if ($annotation instanceof Annotations\Field) {
                    $mapping += (array)$annotation;
                } else if ($annotation instanceof Annotations\ReferenceOne) {
                    $mapping += (array)$annotation;
                    $metadata->mapManyToOne($mapping);
                } else if ($annotation instanceof Annotations\ReferenceMany) {
                    $mapping += (array)$annotation;
                    $metadata->mapManyToMany($mapping);
                }
            }
            if (count($mapping) > 1) {
                $metadata->mapField($mapping);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAllClassNames()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function isTransient($className)
    {
        return false;
    }
}