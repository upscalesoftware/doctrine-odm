<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class IntegerType implements Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDBValue($value)
    {
        return (int)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        return (int)$value;
    }
}