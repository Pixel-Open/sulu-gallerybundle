<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;

class AlbumSelection extends SimpleContentType
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        parent::__construct('album_selection', []);
    }

    /**
     * @return Album[]
     */
    public function getContentData(PropertyInterface $property): array
    {
        $ids = $property->getValue();

        if (empty($ids)) {
            return [];
        }

        $albums = $this->entityManager->getRepository(Album::class)->findBy([
            'id' => $ids,
        ]);

        $idPositions = array_flip($ids);
        usort($albums, function (Album $a, Album $b) use ($idPositions) {
            return $idPositions[$a->getId()] - $idPositions[$b->getId()];
        });

        return $albums;
    }

    /**
     * @return array<string, array<int>|null>
     */
    public function getViewData(PropertyInterface $property): array
    {
        return [
            'ids' => $property->getValue(),
        ];
    }
}
