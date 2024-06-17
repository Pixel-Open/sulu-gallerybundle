<?php

namespace Pixel\GalleryBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Sulu\Bundle\MediaBundle\Entity\MediaInterface;

/**
 * @ORM\Entity()
 * @ORM\Table(name="gallery_album")
 * @ORM\Entity(repositoryClass="Pixel\GalleryBundle\Repository\AlbumRepository")
 * @Serializer\ExclusionPolicy("all")
 */
class Album
{
    public const RESOURCE_KEY = 'albums';
    public const FORM_KEY = 'album_details';
    public const LIST_KEY = 'albums';
    public const SECURITY_CONTEXT = 'gallery.albums';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     *
     * @Serializer\Expose()
     */
    private ?int $id = null;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=false)
     * @Serializer\Expose()
     */
    private $enabled;

    /**
     * @var Collection<string, AlbumTranslation>
     *
     * @ORM\OneToMany(targetEntity="Pixel\GalleryBundle\Entity\AlbumTranslation", mappedBy="album", cascade={"ALL"}, indexBy="locale")
     *
     * @Serializer\Exclude
     */
    private $translations;

    /**
     * @var string
     */
    private $locale = 'fr';

    /**
     * @ORM\Column(type="json", nullable=true)
     *
     * @Serializer\Expose()
     * @var array<mixed>
     */
    private ?array $location = null;

    /**
     * @ORM\ManyToOne(targetEntity=MediaInterface::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private ?MediaInterface $logo = null;

    /**
     * @ORM\ManyToOne(targetEntity=MediaInterface::class)
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @Serializer\Expose()
     */
    private ?MediaInterface $cover = null;

    /**
     * @ORM\Column(type="json", nullable=true)
     * @Serializer\Expose()
     * @var array<mixed>|null
     */
    private ?array $medias;

    public function __construct()
    {
        $this->enabled = false;
        $this->translations = new ArrayCollection();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return array<mixed>|null
     */
    public function getLocation(): ?array
    {
        return $this->location;
    }

    /**
     * @param array<mixed>|null $location
     */
    public function setLocation(?array $location): void
    {
        $this->location = $location;
    }

    /**
     * @return array<string, mixed>
     *
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("logo")
     */
    public function getLogoData(): ?array
    {
        if ($logo = $this->getLogo()) {
            return [
                'id' => $logo->getId(),
            ];
        }

        return null;
    }

    public function getLogo(): ?MediaInterface
    {
        return $this->logo;
    }

    public function setLogo(?MediaInterface $logo): void
    {
        $this->logo = $logo;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * @return AlbumTranslation[]
     */
    public function getTranslations(): array
    {
        return $this->translations->toArray();
    }

    /**
     * @Serializer\VirtualProperty(name="title")
     */
    public function getName(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getName();
    }

    protected function getTranslation(string $locale): ?AlbumTranslation
    {
        if (!$this->translations->containsKey($locale)) {
            return null;
        }

        return $this->translations->get($locale);
    }

    public function setName(string $name): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setName($name);

        return $this;
    }

    protected function createTranslation(string $locale): AlbumTranslation
    {
        $translation = new AlbumTranslation($this, $locale);
        $this->translations->set($locale, $translation);

        return $translation;
    }

    /**
     * @Serializer\VirtualProperty(name="description")
     */
    public function getDescription(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getDescription();
    }

    public function setDescription(?string $description): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setDescription($description);

        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="seo")
     * @return array<mixed>|null
     */
    public function getSeo(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getSeo();
    }

    /**
     * @return array<string, array<string>>
     */
    protected function emptySeo(): array
    {
        return [
            "seo" => [
                "title" => "",
                "description" => "",
                "keywords" => "",
                "canonicalUrl" => "",
                "noIndex" => "",
                "noFollow" => "",
                "hideinSitemap" => "",
            ],
        ];
    }

    /**
     * @Serializer\VirtualProperty(name="ext")
     * @return array<mixed>|null
     */
    public function getExt(): ?array
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }
        return ($translation->getSeo()) ? [
            'seo' => $translation->getSeo(),
        ] : $this->emptySeo();
    }

    /**
     * @param array<mixed>|null $seo
     */
    public function setSeo(?array $seo): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setSeo($seo);

        return $this;
    }

    /**
     * @Serializer\VirtualProperty(name="route")
     */
    public function getRoutePath(): ?string
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            return null;
        }

        return $translation->getRoutePath();
    }

    public function setRoutePath(string $routePath): self
    {
        $translation = $this->getTranslation($this->locale);
        if (!$translation) {
            $translation = $this->createTranslation($this->locale);
        }

        $translation->setRoutePath($routePath);

        return $this;
    }

    public function getCover(): ?MediaInterface
    {
        return $this->cover;
    }

    public function setCover(?MediaInterface $cover): void
    {
        $this->cover = $cover;
    }

    /**
     * @return array<mixed>|null
     */
    public function getMedias(): ?array
    {
        return $this->medias;
    }

    /**
     * @param array<mixed>|null $medias
     */
    public function setMedias(?array $medias): void
    {
        $this->medias = $medias;
    }
}
