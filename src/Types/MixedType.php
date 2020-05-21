<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class MixedType implements Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDBValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        return $value;
    }
}