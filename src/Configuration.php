<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM;

use Doctrine\KeyValueStore\Configuration as KeyValueConfiguration;
use Upscale\Doctrine\ODM\Types\TypeManager;

class Configuration extends KeyValueConfiguration
{
    /**
     * @param array
     */
    private $config;

    /**
     * @param TypeManager $typeManager
     * @return self
     */
    public function setTypeManager(TypeManager $typeManager): self
    {
        $this->config['typeManager'] = $typeManager;
        return $this;
    }

    /**
     * @return TypeManager
     */
    public function getTypeManager(): TypeManager
    {
        if (!isset($this->config['typeManager'])) {
            $this->config['typeManager'] = new TypeManager([
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
        return $this->config['typeManager'];
    }
}
