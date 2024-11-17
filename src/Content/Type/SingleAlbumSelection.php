<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Bundle\ReferenceBundle\Application\Collector\ReferenceCollectorInterface;
use Sulu\Bundle\ReferenceBundle\Infrastructure\Sulu\ContentType\ReferenceContentTypeInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class SingleAlbumSelection extends SimpleContentType implements ReferenceContentTypeInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct('single_album_selection', null);
    }

    public function getContentData(PropertyInterface $property): ?Album
    {
        $id = $property->getValue();

        if (empty($id)) {
            return null;
        }

        return $this->entityManager->getRepository(Album::class)->find($id);
    }

    /**
     * @return array<string, int|null>
     */
    public function getViewData(PropertyInterface $property): array
    {
        return [
            'id' => $property->getValue(),
        ];
    }

    public function getReferences(PropertyInterface $property, ReferenceCollectorInterface $referenceCollector, string $propertyPrefix = ''): void
    {
        $data = $property->getValue();
        if (!isset($data) || !is_int($data)) {
            return;
        }

        $referenceCollector->addReference(
            Album::RESOURCE_KEY,
            (string) $data,
            $propertyPrefix . $property->getName()
        );
    }
}
