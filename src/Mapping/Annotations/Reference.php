<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Mapping\Annotations;

/**
 * @Annotation
 * @Target("PROPERTY")
 */
class Reference
{
    /**
     * @var string
     */
    public $targetDocument;

    /**
     * @var string
     */
    public $mappedBy;
    
    /**
     * @var string
     */
    public $inversedBy;
}