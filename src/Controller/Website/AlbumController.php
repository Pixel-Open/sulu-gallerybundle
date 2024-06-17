<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Controller\Website;

use Pixel\GalleryBundle\Entity\Album;
use Sulu\Bundle\PreviewBundle\Preview\Preview;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\WebsiteBundle\Resolver\TemplateAttributeResolverInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AlbumController extends AbstractController
{
    private TemplateAttributeResolverInterface $templateAttributeResolver;
    private RouteRepositoryInterface $routeRepository;
    private WebspaceManagerInterface $webspaceManager;
    private Environment $twig;

    public function __construct(TemplateAttributeResolverInterface $templateAttributeResolver, RouteRepositoryInterface $routeRepository, WebspaceManagerInterface $webspaceManager, Environment $twig)
    {
        $this->templateAttributeResolver = $templateAttributeResolver;
        $this->routeRepository = $routeRepository;
        $this->webspaceManager = $webspaceManager;
        $this->twig = $twig;
    }

    /**
     * @param array<mixed> $attributes
     * @throws \Exception
     */
    public function indexAction(Album $album, array $attributes = [], bool $preview = false, bool $partial = false): Response
    {
        if (!$album->getSeo() || (isset($album->getSeo()['title'])) && !$album->getSeo()['title']) {
            $seo = [
                "title" => $album->getName(),
            ];

            $album->setSeo($seo);
        }
        $parameters = $this->templateAttributeResolver->resolve([
            'album' => $album,
            'localizations' => $this->getLocalizationsArrayForEntity($album),
        ]);

        if ($partial) {
            return $this->renderBlock(
                '@Gallery/album.html.twig',
                'content',
                $parameters
            );
        } elseif ($preview) {
            $content = $this->renderPreview(
                '@Gallery/album.html.twig',
                $parameters
            );
        } else {
            if (!$album->isEnabled()) {
                throw $this->createNotFoundException();
            }
            $content = $this->renderView(
                '@Gallery/album.html.twig',
                $parameters
            );
        }

        return new Response($content);
    }

    /**
     * @return array<string, array<mixed>>
     */
    protected function getLocalizationsArrayForEntity(Album $entity): array
    {
        $routes = $this->routeRepository->findAllByEntity(Album::class, (string)$entity->getId());

        $localizations = [];
        foreach ($routes as $route) {
            $url = $this->webspaceManager->findUrlByResourceLocator(
                $route->getPath(),
                null,
                $route->getLocale()
            );

            $localizations[$route->getLocale()] = [
                'locale' => $route->getLocale(),
                'url' => $url,
            ];
        }

        return $localizations;
    }

    /**
     * @param array<mixed> $parameters
     */
    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;
        //$album = $parameters['album'];

        return $this->renderView('@SuluWebsite/Preview/preview.html.twig', $parameters);
    }
}
