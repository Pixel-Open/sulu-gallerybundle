<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Sitemap;

use Pixel\GalleryBundle\Repository\AlbumRepository;
use Sulu\Bundle\WebsiteBundle\Sitemap\Sitemap;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapProviderInterface;
use Sulu\Bundle\WebsiteBundle\Sitemap\SitemapUrl;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class GallerySitemapProvider implements SitemapProviderInterface
{
    private AlbumRepository $albumRepository;
    private WebspaceManagerInterface $webspaceManager;
    /**
     * @var array<string>
     */
    private array $locales = [];

    public function __construct(AlbumRepository $albumRepository, WebspaceManagerInterface $webspaceManager)
    {
        $this->albumRepository = $albumRepository;
        $this->webspaceManager = $webspaceManager;
    }

    public function build($page, $scheme, $host): array
    {
        $locale = $this->getLocaleByHost($host);
        $result = [];
        foreach ($this->albumRepository->findAllForSitemap((int)$page, (int)self::PAGE_SIZE) as $album) {
            //$album->setLocale($locale);
            $result[] = new SitemapUrl(
                $scheme . '://' . $host . $album->getRoutePath(),
                $album->getLocale(),
                $album->getLocale(),
                new \DateTime()
            );
        }

        return $result;
    }

    private function getLocaleByHost(string $host): ?string
    {
        if (!\array_key_exists($host, $this->locales)) {
            $portalInformation = $this->webspaceManager->getPortalInformations();
            foreach ($portalInformation as $hostName => $portal) {
                if ($hostName === $host) {
                    $this->locales[$host] = $portal->getLocale();
                }
            }
        }
        if (isset($this->locales[$host])) {
            return $this->locales[$host];
        }
        return null;
    }

    public function createSitemap($scheme, $host): Sitemap
    {
        return new Sitemap($this->getAlias(), $this->getMaxPage($scheme, $host));
    }

    public function getAlias(): string
    {
        return 'gallery';
    }

    public function getMaxPage($scheme, $host)
    {
        return (int) ceil($this->albumRepository->countForSitemap() / self::PAGE_SIZE);
    }
}
