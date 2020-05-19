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
     * {@inheritdoc}
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
            $fieldInfo = $this->reader->getPropertyAnnotation($property, Annotations\Field::class);
            if ($fieldInfo instanceof Annotations\Field) {
                $fieldName = $property->getName();
                if ($this->reader->getPropertyAnnotation($property, Annotations\Id::class)) {
                    $metadata->mapIdentifier($fieldName);
                }
                $metadata->mapField([
                    'fieldName' => $fieldName,
                ]);
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