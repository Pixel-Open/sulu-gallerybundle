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

class AlbumController extends AbstractController
{
    /**
     * @return string[]
     */
    public static function getSubscribedServices()
    {
        $subscribedServices = parent::getSubscribedServices();

        $subscribedServices['sulu_core.webspace.webspace_manager'] = WebspaceManagerInterface::class;
        $subscribedServices['sulu.repository.route'] = RouteRepositoryInterface::class;
        $subscribedServices['sulu_website.resolver.template_attribute'] = TemplateAttributeResolverInterface::class;

        return $subscribedServices;
    }

    public function indexAction(Album $album, $attributes = [], $preview = false, $partial = false): Response
    {


        $parameters = $this->get('sulu_website.resolver.template_attribute')->resolve([
            'album' => $album,
            'localizations' => $this->getLocalizationsArrayForEntity($album),
        ]);

        if ($partial) {
            $content = $this->renderBlock(
                '@Gallery/album.html.twig',
                'content',
                $parameters
            );
        } else if ($preview) {
            $content = $this->renderPreview(
                '@Gallery/album.html.twig',
                $parameters
            );
        } else {
            if (!$album->isEnabled()) throw $this->createNotFoundException();
            $content = $this->renderView(
                '@Gallery/album.html.twig',
                $parameters
            );
        }

        return new Response($content);
    }

    /**
     * @return array<string, array>
     */
    protected function getLocalizationsArrayForEntity(Album $entity): array
    {
        $routes = $this->get('sulu.repository.route')->findAllByEntity(Album::class, (string)$entity->getId());

        $localizations = [];
        foreach ($routes as $route) {
            $url = $this->get('sulu_core.webspace.webspace_manager')->findUrlByResourceLocator(
                $route->getPath(),
                null,
                $route->getLocale()
            );

            $localizations[$route->getLocale()] = ['locale' => $route->getLocale(), 'url' => $url];
        }

        return $localizations;
    }

    /**
     * Returns rendered part of template specified by block.
     *
     * @param mixed $template
     * @param mixed $block
     * @param mixed $attributes
     */
    protected function renderBlock($template, $block, $attributes = [])
    {
        $twig = $this->get('twig');
        $attributes = $twig->mergeGlobals($attributes);

        $template = $twig->load($template);

        $level = ob_get_level();
        ob_start();

        try {
            $rendered = $template->renderBlock($block, $attributes);
            ob_end_clean();

            return $rendered;
        }
        catch (\Exception $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    protected function renderPreview(string $view, array $parameters = []): string
    {
        $parameters['previewParentTemplate'] = $view;
        $parameters['previewContentReplacer'] = Preview::CONTENT_REPLACER;
        //$album = $parameters['album'];

        return $this->renderView('@SuluWebsite/Preview/preview.html.twig', $parameters);
    }
}