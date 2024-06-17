<?php

namespace Pixel\GalleryBundle\Routing;

use Pixel\GalleryBundle\Controller\Website\AlbumController;
use Pixel\GalleryBundle\Entity\Album;
use Pixel\GalleryBundle\Repository\AlbumRepository;
use Sulu\Bundle\RouteBundle\Routing\Defaults\RouteDefaultsProviderInterface;

class AlbumRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    private AlbumRepository $albumRepository;

    public function __construct(AlbumRepository $albumRepository)
    {
        $this->albumRepository = $albumRepository;
    }

    /**
     * @return mixed[]
     */
    public function getByEntity($entityClass, $id, $locale, $object = null)
    {
        return [
            '_controller' => AlbumController::class . '::indexAction',
            'album' => $object ?: $this->albumRepository->findById((int)$id, $locale),
        ];
    }

    public function isPublished($entityClass, $id, $locale)
    {
        $album = $this->albumRepository->findById((int)$id, $locale);
        if (!$this->supports($entityClass) || !$album instanceof Album) {
            return false;
        }
        return $album->isEnabled();
    }

    public function supports($entityClass)
    {
        return Album::class === $entityClass;
    }
}
