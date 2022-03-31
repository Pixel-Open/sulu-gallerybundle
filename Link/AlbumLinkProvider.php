<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Link;

use Pixel\GalleryBundle\Entity\Album;
use Pixel\GalleryBundle\Repository\AlbumRepository;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkConfigurationBuilder;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkItem;
use Sulu\Bundle\MarkupBundle\Markup\Link\LinkProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class AlbumLinkProvider implements LinkProviderInterface
{
    private AlbumRepository $albumRepository;
    private TranslatorInterface $translator;

    public function __construct(AlbumRepository $albumRepository, TranslatorInterface $translator)
    {
        $this->albumRepository = $albumRepository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return LinkConfigurationBuilder::create()
            ->setTitle($this->translator->trans('gallery'))
            ->setResourceKey(Album::RESOURCE_KEY) // the resourceKey of the entity that should be loaded
            ->setListAdapter('column_list')
            ->setDisplayProperties(['name'])
            ->setOverlayTitle($this->translator->trans('gallery'))
            ->setEmptyText($this->translator->trans('gallery.emptyGallery'))
            ->setIcon('fa-images')
            ->getLinkConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function preload(array $hrefs, $locale, $published = true): array
    {
        if (0 === count($hrefs)) {
            return [];
        }

        $items = $this->albumRepository->findBy(['id' => $hrefs]); // load items by id
        foreach ($items as $item) {
            $result[] = new LinkItem($item->getId(), $item->getName(), $item->getRoutePath(), $item->isEnabled()); // create link-item foreach item
        }

        return $result;
    }
}
