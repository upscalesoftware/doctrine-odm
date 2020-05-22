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
    /**#@+
     * Association types
     */
    const ONE_TO_ONE    = 1;
    const ONE_TO_MANY   = 2;
    const MANY_TO_ONE   = 4;
    const MANY_TO_MANY  = 8;
    /**#@-*/
    
    /**#@+
     * Association cardinality 
     */
    const TO_ONE  = self::ONE_TO_ONE  | self::MANY_TO_ONE; 
    const TO_MANY = self::ONE_TO_MANY | self::MANY_TO_MANY;
    /**#@-*/

    /**
     * @var string
     */
    public $namespace;

    /**
     * @var bool
     */
    public $embedded = false;

    /**
     * @var string[]
     */
    public $fieldNames = [];

    /**
     * @var array
     */
    public $associations = [];

    /**
     * Inject dependencies
     * 
     * @param string $className
     */
    public function __construct($className)
    {
        parent::__construct($className);
        $this->namespace = substr($className, 0, (int)strrpos($className, '\\'));
    }

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
     * {@inheritdoc}
     */
    public function getTypeOfField($fieldName)
    {
        return $this->fields[$fieldName]['type'] ?? 'mixed';
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
     * @param array $mapping
     * @throws \InvalidArgumentException
     */
    public function mapManyToOne($mapping)
    {
        $this->mapAssociation(self::MANY_TO_ONE, $mapping);
    }

    /**
     * @param array $mapping
     * @throws \InvalidArgumentException
     */
    public function mapManyToMany($mapping)
    {
        $this->mapAssociation(self::MANY_TO_MANY, $mapping);
    }

    /**
     * @param int $type
     * @param array $mapping
     * @throws \InvalidArgumentException
     */
    protected function mapAssociation(int $type, array $mapping)
    {
        if (empty($mapping['targetDocument'])) {
            throw new \InvalidArgumentException('Association is missing target document.');
        }
        if ($this->namespace && strpos($mapping['targetDocument'], '\\') === false) {
            $mapping['targetDocument'] = $this->namespace . '\\' . $mapping['targetDocument'];
        }
        if (!class_exists($mapping['targetDocument'])) {
            throw new \InvalidArgumentException('Association references unknown target document.');
        }
        $mapping['sourceDocument'] = $this->name;
        $mapping['type'] = $type;
        $this->associations[$mapping['fieldName']] = $mapping;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationNames()
    {
        return array_keys($this->associations);
    }

    /**
     * @param object $object
     * @return object[]
     */
    public function getAssociationValues($object): array
    {
        $result = [];
        foreach ($this->getAssociationNames() as $assocName) {
            $value = $this->reflFields[$assocName]->getValue($object);
            if ($value !== null) {
                $result[$assocName] = $value;
            }
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->associations[$fieldName]);
    }

    /**
     * {@inheritdoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return isset($this->associations[$fieldName]) && ($this->associations[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * {@inheritdoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->associations[$fieldName]) && ($this->associations[$fieldName]['type'] & self::TO_MANY);
    }

    /**
     * {@inheritdoc}
     */
    public function isAssociationInverseSide($assocName)
    {
        return (bool)$this->getAssociationMappedByTargetField($assocName);
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationMappedByTargetField($assocName)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssociationTargetClass($assocName)
    {
        return $this->associations[$assocName]['targetDocument'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        $fields = parent::__sleep();
        $fields[] = 'embedded';
        $fields[] = 'fieldNames';
        $fields[] = 'associations';
        return $fields;
    }
}