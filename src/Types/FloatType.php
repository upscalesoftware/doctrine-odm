<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class FloatType implements Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDBValue($value)
    {
        return (float)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        return (float)$value;
    }
}