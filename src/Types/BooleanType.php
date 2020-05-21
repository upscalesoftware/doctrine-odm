<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class BooleanType implements Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDBValue($value)
    {
        return (bool)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        return (bool)$value;
    }
}