<?php

namespace Pixel\GalleryBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Pixel\GalleryBundle\Entity\Setting;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingsExtension extends AbstractExtension
{
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('gallery_settings', [$this, 'gallerySettings']),
        ];
    }

    public function gallerySettings()
    {
        return $this->entityManager->getRepository(Setting::class)->findOneBy([]);
    }
}
