<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Types;

class DateTimeType implements Type
{
    /**#@+
     * Date/time formats
     */
    const DATE  = 'Y-m-d';
    const TIME  = 'H:i:s';
    /**#@-*/
    
    /**
     * @var string
     */
    private $format;

    /**
     * Inject dependencies
     * 
     * @param string $format
     */
    public function __construct(string $format = \DateTime::RFC3339)
    {
        $this->format = $format;
    }

    /**
     * @param \DateTimeImmutable $value
     * @return string
     */
    public function convertToDBValue($value)
    {
        return $value->format($this->format);
    }

    /**
     * @param string $value
     * @return \DateTimeInterface
     */
    public function convertToPHPValue($value)
    {
        return \DateTimeImmutable::createFromFormat($this->format, (string)$value);
    }
}