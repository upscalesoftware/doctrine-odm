<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class StringType implements Type
{
    /**
     * {@inheritdoc}
     */
    public function convertToDBValue($value)
    {
        return (string)$value;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        return (string)$value;
    }
}