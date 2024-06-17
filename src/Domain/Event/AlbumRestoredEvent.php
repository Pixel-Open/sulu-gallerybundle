<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Domain\Event;

use Pixel\GalleryBundle\Entity\Album;
use Sulu\Bundle\ActivityBundle\Domain\Event\DomainEvent;

class AlbumRestoredEvent extends DomainEvent
{
    private Album $album;
    /**
     * @var array<mixed>
     */
    private array $payload;

    /**
     * @param array<mixed> $payload
     */
    public function __construct(Album $album, array $payload)
    {
        parent::__construct();
        $this->album = $album;
        $this->payload = $payload;
    }

    public function getAlbum(): Album
    {
        return $this->album;
    }

    public function getEventPayload(): ?array
    {
        return $this->payload;
    }

    public function getEventType(): string
    {
        return 'restored';
    }

    public function getResourceKey(): string
    {
        return Album::RESOURCE_KEY;
    }

    public function getResourceId(): string
    {
        return (string)$this->album->getId();
    }

    public function getResourceTitle(): ?string
    {
        return $this->album->getName();
    }

    public function getResourceSecurityContext(): ?string
    {
        return Album::SECURITY_CONTEXT;
    }
}
