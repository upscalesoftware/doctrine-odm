<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class TypeManager
{
    /**
     * @var Type[]
     */
    private $types = [];

    /**
     * Inject dependencies
     * 
     * @param array $types
     */
    public function __construct(array $types = [])
    {
        foreach ($types as $name => $type) {
            $this->add($name, $type);
        }
    }

    /**
     * @param string $name
     * @param Type $type
     */
    private function add(string $name, Type $type)
    {
        $this->types[$name] = $type;
    }

    /**
     * @param string $name
     * @return Type
     * @throws \InvalidArgumentException
     */
    public function get(string $name): Type
    {
        if (!isset($this->types[$name])) {
            throw new \InvalidArgumentException("Type '$name' is not recognized.");
        }
        return $this->types[$name];
    }
}