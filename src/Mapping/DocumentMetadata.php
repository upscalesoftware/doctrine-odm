<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping;

use Doctrine\KeyValueStore\Mapping\ClassMetadata;

/**
 * @property \ReflectionClass $reflClass
 * @property \ReflectionProperty[] $reflFields
 */
class DocumentMetadata extends ClassMetadata
{
    /**
     * @var string[]
     */
    public $fieldNames = [];

    /**
     * {@inheritdoc}
     */
    public function mapField($mapping)
    {
        $fieldName = $mapping['fieldName'];
        $mappedName = $mapping['name'] ?? $fieldName;
        $this->fieldNames[$mappedName] = $fieldName;
        parent::mapField($mapping);
    }

    /**
     * @param string $fieldName
     * @return string
     */
    public function resolveFieldName(string $fieldName): string
    {
        return $this->fields[$fieldName]['name'] ?? $fieldName;
    }

    /**
     * @param string $mappedName
     * @return string
     */
    public function restoreFieldName(string $mappedName): string
    {
        return $this->fieldNames[$mappedName] ?? $mappedName;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $fields = parent::__sleep();
        $fields[] = 'fieldNames';
        return $fields;
    }
}