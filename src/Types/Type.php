<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

interface Type
{
    /**
     * @param mixed $value
     * @return mixed
     */
    public function convertToDBValue($value);

    /**
     * @param mixed $value
     * @return mixed
     */
    public function convertToPHPValue($value);
}