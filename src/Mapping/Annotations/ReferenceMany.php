<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
final class ReferenceMany extends Reference
{
    /**
     * @var string
     */
    public $collectionClass;
}