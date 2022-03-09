<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\ViewHandlerInterface;
use Pixel\DirectoryBundle\Entity\Card;
use Pixel\GalleryBundle\Common\DoctrineListRepresentationFactory;
use Pixel\GalleryBundle\Domain\Event\AlbumCreatedEvent;
use Pixel\GalleryBundle\Domain\Event\AlbumModifiedEvent;
use Pixel\GalleryBundle\Domain\Event\AlbumRemovedEvent;
use Pixel\GalleryBundle\Entity\Album;
use Pixel\GalleryBundle\Repository\AlbumRepository;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

//use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
//use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;

/**
 * @RouteResource("album")
 */
class AlbumController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{

    use RequestParametersTrait;

    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;
    private EntityManagerInterface $entityManager;
    private MediaManagerInterface $mediaManager;
    private CategoryManagerInterface $categoryManager;
    private WebspaceManagerInterface $webspaceManager;
    private RouteManagerInterface $routeManager;
    private RouteRepositoryInterface $routeRepository;
    private TrashManagerInterface $trashManager;
    private DomainEventCollectorInterface $domainEventCollector;
    private AlbumRepository $repository;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface            $entityManager,
        MediaManagerInterface             $mediaManager,
        ViewHandlerInterface              $viewHandler,
        CategoryManagerInterface          $categoryManager,
        WebspaceManagerInterface          $webspaceManager,
        RouteManagerInterface             $routeManager,
        RouteRepositoryInterface          $routeRepository,
        TrashManagerInterface             $trashManager,
        DomainEventCollectorInterface     $domainEventCollector,
        AlbumRepository                   $repository,
        ?TokenStorageInterface            $tokenStorage = null
    )
    {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->categoryManager = $categoryManager;
        $this->webspaceManager = $webspaceManager;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->repository = $repository;
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Album::RESOURCE_KEY,
            [],
            ['locale' => $locale]
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id, Request $request): Response
    {
        $entity = $this->load($id, $request);
        if (!$entity) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($entity));
    }

    protected function load(int $id, Request $request): ?Album
    {
        return $this->repository->findById($id, (string)$this->getLocale($request));
    }

    public function putAction(Request $request, int $id): Response
    {
        $entity = $this->load($id, $request);
        if (!$entity) {
            throw new NotFoundHttpException();
        }

        $data = $request->request->all();
        $this->mapDataToEntity($data, $entity);
        $this->updateRoutesForEntity($entity);
        $this->domainEventCollector->collect(
            new AlbumModifiedEvent($entity, $data)
        );
        $this->entityManager->flush();
        $this->save($entity);

        return $this->handleView($this->view($entity));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Album $entity): void
    {
        $logoId = $data['logo']['id'] ?? null;
        $location = $data['location'] ?? null;
        $seo = (isset($data['ext']['seo'])) ? $data['ext']['seo'] : null;
        $description = $data['description'] ?? null;
        $coverId = $data['cover']['id'] ?? null;
        $medias = $data['medias'] ?? null;

        $entity->setName($data['name']);
        $entity->setDescription($description);
        $entity->setSeo($seo);
        $entity->setRoutePath($data['routePath']);
        $entity->setCover($coverId ? $this->mediaManager->getEntityById($coverId) : null);
        $entity->setMedias($medias);

        //$entity->setLogo($logoId ? $this->mediaManager->getEntityById($logoId) : null);
    }

    protected function updateRoutesForEntity(Album $entity): void
    {
        // create route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $this->routeManager->createOrUpdateByAttributes(
                Album::class,
                (string)$entity->getId(),
                $locale,
                $entity->getRoutePath(),
            );
        }
    }

    protected function save(Album $entity): void
    {
        $this->repository->save($entity);
    }

    public function postAction(Request $request): Response
    {
        $entity = $this->create($request);
        $data = $request->request->all();
        $this->mapDataToEntity($data, $entity);
        $this->save($entity);
        $this->updateRoutesForEntity($entity);
        $this->domainEventCollector->collect(
            new AlbumCreatedEvent($entity, $data)
        );
        $this->entityManager->flush();

        return $this->handleView($this->view($entity, 201));
    }

    protected function create(Request $request): Album
    {
        return $this->repository->create((string)$this->getLocale($request));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Album $album */
        $album = $this->entityManager->getRepository(Album::class)->find($id);
        $albumName = $album->getName();
        if ($album) {
            $this->trashManager->store(Album::RESOURCE_KEY, $album);
            $this->entityManager->remove($album);
            $this->removeRoutesForEntity($album);
            $this->domainEventCollector->collect(
                new AlbumRemovedEvent($id, $albumName)
            );
        }
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    protected function removeRoutesForEntity(Album $entity): void
    {
        // remove route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $routes = $this->routeRepository->findAllByEntity(
                Album::class,
                (string)$entity->getId(),
                $locale
            );

            foreach ($routes as $route) {
                $this->routeRepository->remove($route);
            }
        }
    }

    public function getSecurityContext(): string
    {
        return Card::SECURITY_CONTEXT;
    }

    /**
     * @Rest\Post("/albums/{id}")
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityNotFoundException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {

        $album = $this->repository->findById($id, (string)$this->getLocale($request));
        if (!$album) {
            throw new NotFoundHttpException();
        }

        switch ($request->query->get('action')) {
            case 'enable':
                $album->setEnabled(true);
                break;
            case 'disable':
                $album->setEnabled(false);
                break;
        }

        $this->repository->save($album);

        return $this->handleView($this->view($album));
    }

}
