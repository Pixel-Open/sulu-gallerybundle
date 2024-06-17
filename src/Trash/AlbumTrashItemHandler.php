<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Trash;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GalleryBundle\Admin\AlbumAdmin;
use Pixel\GalleryBundle\Domain\Event\AlbumRestoredEvent;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\TrashBundle\Application\DoctrineRestoreHelper\DoctrineRestoreHelperInterface;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfiguration;
use Sulu\Bundle\TrashBundle\Application\RestoreConfigurationProvider\RestoreConfigurationProviderInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\RestoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashItemHandler\StoreTrashItemHandlerInterface;
use Sulu\Bundle\TrashBundle\Domain\Model\TrashItemInterface;
use Sulu\Bundle\TrashBundle\Domain\Repository\TrashItemRepositoryInterface;

class AlbumTrashItemHandler implements StoreTrashItemHandlerInterface, RestoreTrashItemHandlerInterface, RestoreConfigurationProviderInterface
{
    private TrashItemRepositoryInterface $trashItemRepository;
    private EntityManagerInterface $entityManager;
    private DoctrineRestoreHelperInterface $doctrineRestoreHelper;
    private DomainEventCollectorInterface $domainEventCollector;

    public function __construct(
        TrashItemRepositoryInterface   $trashItemRepository,
        EntityManagerInterface         $entityManager,
        DoctrineRestoreHelperInterface $doctrineRestoreHelper,
        DomainEventCollectorInterface  $domainEventCollector
    ) {
        $this->trashItemRepository = $trashItemRepository;
        $this->entityManager = $entityManager;
        $this->doctrineRestoreHelper = $doctrineRestoreHelper;
        $this->domainEventCollector = $domainEventCollector;
    }

    public static function getResourceKey(): string
    {
        return Album::RESOURCE_KEY;
    }

    public function store(object $resource, array $options = []): TrashItemInterface
    {
        $cover = $resource->getCover();

        $data = [
            "name" => $resource->getName(),
            "description" => $resource->getDescription(),
            "slug" => $resource->getRoutePath(),
            "seo" => $resource->getSeo(),
            "enabled" => $resource->isEnabled(),
            "location" => $resource->getLocation(),
            "coverId" => $cover ? $cover->getId() : null,
            "medias" => $resource->getMedias(),
        ];

        return $this->trashItemRepository->create(
            Album::RESOURCE_KEY,
            (string)$resource->getId(),
            $resource->getName(),
            $data,
            null,
            $options,
            Album::SECURITY_CONTEXT,
            null,
            null
        );
    }

    public function restore(TrashItemInterface $trashItem, array $restoreFormData = []): object
    {
        $data = $trashItem->getRestoreData();
        $albumId = (int)$trashItem->getResourceId();
        $album = new Album();
        $album->setName($data['name']);
        $album->setDescription($data['description']);
        $album->setRoutePath($data['slug']);
        $album->setSeo($data['seo']);
        $album->setEnabled($data['enabled']);
        $album->setLocation($data['location']);
        if ($data['coverId']) {
            $album->setCover($this->entityManager->find(MediaInterface::class, $data['coverId']));
        }
        $album->setMedias($data['medias']);
        $this->domainEventCollector->collect(
            new AlbumRestoredEvent($album, $data)
        );

        $this->doctrineRestoreHelper->persistAndFlushWithId($album, $albumId);
        $this->createRoute($this->entityManager, $albumId, $album->getRoutePath(), Album::class);
        $this->entityManager->flush();
        return $album;
    }

    private function createRoute(EntityManagerInterface $manager, int $id, string $slug, string $class): void
    {
        $route = new Route();
        $route->setPath($slug);
        $route->setLocale('fr');
        $route->setEntityClass($class);
        $route->setEntityId((string) $id);
        $route->setHistory(false);
        $route->setCreated(new \DateTime());
        $route->setChanged(new \DateTime());
        $manager->persist($route);
    }

    public function getConfiguration(): RestoreConfiguration
    {
        return new RestoreConfiguration(null, AlbumAdmin::EDIT_FORM_VIEW, [
            'id' => 'id',
        ]);
    }
}
