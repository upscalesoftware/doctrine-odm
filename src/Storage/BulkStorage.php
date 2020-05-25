<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM\Storage;

interface BulkStorage
{
    /**
     * @param string $storageName
     * @return object[]
     */
    public function findAll($storageName): array;
}