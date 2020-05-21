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
        /**
         * Parse 3-digit milliseconds using 6-digit format as a workaround
         * @link https://www.php.net/manual/en/datetime.createfromformat.php
         */
        $format = strtr($this->format, ['\v' => '\v', 'v' => 'u']);
        return \DateTimeImmutable::createFromFormat($format, (string)$value);
    }
}