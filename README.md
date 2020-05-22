Doctrine Object Document Mapper
===============================

This project brings a storage-agnostic Object Document Mapper (ODM) to the [Doctrine](https://github.com/doctrine) ecosystem.

The implementation is based on the Doctrine [Key-Value Store](https://github.com/doctrine/KeyValueStore).
It is also heavily inspired by the Doctrine [CouchDB ODM](https://github.com/doctrine/couchdb-odm).


**Features:**
- Metadata in DocBlock annotations
- Standalone and embedded documents
- Document collection mapping: `Article` <-> `blog_posts`
- Simple and composite identifiers
- Field name mapping: `createdAt` <-> `created_at`
- Field data types:
  - Scalar types: `boolean`, `float`, `integer`, `string`, and `mixed` 
  - Date/time types: `date`, `time`, `datetime`
- Unidirectional [associations](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/unitofwork-associations.html): Many-to-One, Many-to-Many
- Cascade persistence of associations

## Installation

The library is to be installed via [Composer](https://getcomposer.org/) as a dependency:
```bash
composer require upscale/doctrine-odm
```

## Usage

### Domain Model

The document model below implements blog Articles authored by Users who along with guests can leave Comments.

```php
use Doctrine\Common\Collections\ArrayCollection;
use Upscale\Doctrine\ODM\Mapping\Annotations as ODM;

/**
 * @ODM\Document(collection="blog_posts")
 */
class Article
{
    /**
     * @ODM\Id
     * @ODM\Field(type="string")
     */
    private $slug;

    /**
     * @ODM\Field(type="string")
     */
    private $title;

    /**
     * @ODM\ReferenceOne(targetDocument="User")
     */
    private $author;

    /**
     * @ODM\Field(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @ODM\ReferenceMany(targetDocument="Comment")
     */
    private $comments;

    public function __construct(string $title, User $author)
    {
        $this->slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($title));
        $this->title = $title;
        $this->author = $author;
        $this->createdAt = new \DateTimeImmutable();
        $this->comments = new ArrayCollection();
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }
}

/**
 * @ODM\EmbeddedDocument
 */
class Comment
{
    /**
     * @ODM\Field(type="string")
     */
    private $message;

    /**
     * @ODM\Field(name="posted_by")
     * @ODM\ReferenceOne(targetDocument="User")
     */
    private $author;

    public function __construct(string $message, User $author = null)
    {
        $this->message = $message;
        $this->author = $author;
    }
}

/**
 * @ODM\Document(collection="blog_users")
 */
class User
{
    /**
     * @ODM\Id
     * @ODM\Field(type="string")
     */
    private $email;

    /**
     * @ODM\Field(type="string")
     */
    private $name;

    public function __construct(string $email, string $name)
    {
        $this->email = $email;
        $this->name = $name;
    }
}
```

### Bootstrap

The following boilerplate initializes the document manager:
```php
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\KeyValueStore\Configuration;
use Upscale\Doctrine\ODM\DocumentManager;
use Upscale\Doctrine\ODM\Mapping\AnnotationDriver;
use Upscale\Doctrine\ODM\Storage\MemoryStorage;

$storage = new MemoryStorage();

$config = new Configuration();
$config->setMappingDriverImpl(new AnnotationDriver(new AnnotationReader()));
$config->setMetadataCache(new ArrayCache);

$dm = new DocumentManager($storage, $config);
```

### CRUD

Create a blog post:
```php
$author = new User('sergey@shymko.net', 'Sergii Shymko');
$article = new Article('Doctrine Object Document Mapper', $author);

$dm->persist($article);
$dm->flush();
```

Read the blog post and update it with comments:
```php
/** @var Article $article */
$article = $dm->find(Article::class, 'doctrine-object-document-mapper');

$author = new User('john.doe@example.com', 'John Doe');
$comment = new Comment('Finally Doctrine has an ODM!', $author);
$article->addComment($comment);

$comment = new Comment('MongoDB ODM is more feature-rich though');
$article->addComment($comment);

$dm->persist($article);
$dm->flush();
```

Delete the blog post:
```php
$dm->remove($article);
$dm->flush();
```

### Storage

Inspect the data structure of the in-memory storage (before deletion):
```php
echo json_encode($storage, JSON_PRETTY_PRINT);
```
```json
{
    "blog_users": {
        "sergey@shymko.net": {
            "email": "sergey@shymko.net",
            "name": "Sergii Shymko"
        },
        "john.doe@example.com": {
            "email": "john.doe@example.com",
            "name": "John Doe"
        }
    },
    "blog_posts": {
        "doctrine-object-document-mapper": {
            "slug": "doctrine-object-document-mapper",
            "title": "Doctrine Object Document Mapper",
            "author": {
                "email": "sergey@shymko.net"
            },
            "created_at": "2020-05-21T22:47:17-07:00",
            "comments": [
                {
                    "message": "Finally Doctrine has an ODM!",
                    "posted_by": {
                        "email": "john.doe@example.com"
                    }
                },
                {
                    "message": "MongoDB ODM is more feature-rich though",
                    "posted_by": null
                }
            ]
        }
    }
}
```

## Contributing

Pull Requests with fixes and improvements are welcome!

## License

Copyright © Upscale Software. All rights reserved.

Licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0).