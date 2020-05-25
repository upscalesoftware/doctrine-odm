<?php
declare(strict_types=1);

namespace Upscale\Doctrine\ODM;

use Doctrine\KeyValueStore\NotFoundException;
use Doctrine\Persistence\ObjectRepository;
use Upscale\Doctrine\ODM\Mapping\DocumentMetadata;
use Upscale\Doctrine\ODM\Storage\BulkStorage;

class DocumentRepository implements ObjectRepository
{
    /**
     * @var UnitOfWork
     */
    private $unitOfWork;
    
    /**
     * @var DocumentMetadata
     */
    private $class;

    /**
     * Inject dependencies
     * 
     * @param UnitOfWork $unitOfWork
     * @param DocumentMetadata $documentMetadata
     */
    public function __construct(UnitOfWork $unitOfWork, DocumentMetadata $documentMetadata)
    {
        $this->unitOfWork = $unitOfWork;
        $this->class = $documentMetadata;
    }

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        try {
            return $this->unitOfWork->reconstitute($this->class->name, $id);
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $storage = $this->unitOfWork->getStorage();
        if (!$storage instanceof BulkStorage) {
            throw new \LogicException('Storage driver does not support bulk operations.');
        }
        $items = $storage->findAll($this->class->storageName);
        $result = [];
        foreach ($items as $data) {
            $result[] = $this->unitOfWork->createDocument($this->class, $data);
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->class->name;
    }
}
