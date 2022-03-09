<?php

declare(strict_types=1);

namespace Pixel\GalleryBundle\Preview;

use Pixel\GalleryBundle\Entity\Album;
use Pixel\GalleryBundle\Repository\AlbumRepository;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;

class AlbumObjectProvider implements PreviewObjectProviderInterface
{
    private AlbumRepository $albumRepository;
    private MediaManagerInterface $mediaManager;

    public function __construct(AlbumRepository $albumRepository, MediaManagerInterface $mediaManager)
    {
        $this->albumRepository = $albumRepository;
        $this->mediaManager = $mediaManager;
    }

    public function getObject($id, $locale): Album
    {
        return $this->albumRepository->findById((int)$id, $locale);
    }

    public function getId($object): int
    {
        return $object->getId();
    }

    public function setValues($object, $locale, array $data)
    {
        $coverId = $data['cover']['id'] ?? null;
        $location = $data['location'] ?? null;
        $enabled = $data['enabled'] ?? null;
        $medias = $data['medias'] ?? null;

        $object->setName($data['name']);
        $object->setDescription($data['description']);
        $object->setCover($coverId ? $this->mediaManager->getEntityById($coverId) : null);
        $object->setLocation($location);
        $object->setEnabled($enabled);
        $object->setMedias($medias);
        //return $object;
    }

    public function setContext($object, $locale, array $context)
    {
        if (\array_key_exists('template', $context)) {
            $object->setStructureType($context['template']);
        }

        return $object;
    }

    /**
     * @param Album $object
     * @return string
     */
    public function serialize($object): string
    {
        if (!$object->getName()) $object->setName('name');
        if (!$object->getDescription()) $object->setDescription('description');

        return serialize($object);
    }

    public function deserialize($serializedObject, $objectClass): Album
    {
        return unserialize($serializedObject);
    }

    public function getSecurityContext($id, $locale): ?string
    {
        // TODO: Implement getSecurityContext() method.
    }
}