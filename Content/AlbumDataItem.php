<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Content;

use JMS\Serializer\Annotation as Serializer;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Component\SmartContent\ItemInterface;

/**
 * @Serializer\ExclusionPolicy("all")
 */
class AlbumDataItem implements ItemInterface
{

    private $entity;

    public function __construct(Album $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getId(): string
    {
        return (string)$this->entity->getId();
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getTitle(): string
    {
        return (string)$this->entity->getName();
    }

    /**
     * @Serializer\VirtualProperty
     */
    public function getImage(): ?string
    {
        return null;
    }

    public function getResource(): Album
    {
        return $this->entity;
    }
}