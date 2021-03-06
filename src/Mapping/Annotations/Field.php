<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class Field
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $type;
}