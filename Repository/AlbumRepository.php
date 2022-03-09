<?php

namespace Pixel\GalleryBundle\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Pixel\GalleryBundle\Entity\Album;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait;


class AlbumRepository extends EntityRepository implements DataProviderRepositoryInterface
{
    use DataProviderRepositoryTrait;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, new ClassMetadata(Album::class));
    }

    public function create(string $locale): Album
    {
        $album = new Album();
        $album->setLocale($locale);
        return $album;
    }

    public function save(Album $album): void
    {
        $this->getEntityManager()->persist($album);
        $this->getEntityManager()->flush();
    }

    public function findById(int $id, string $locale): ?Album
    {
        $album = $this->find($id);
        if (!$album) {
            return null;
        }

        $album->setLocale($locale);

        return $album;
    }

    public function findAllForSitemap(int $page, int $limit): array
    {
        $offset = ($page * $limit) - $limit;
        $criteria = [
            'enabled' => true,
        ];
        return $this->findBy($criteria, [], $limit, $offset);
    }

    public function countForSitemap()
    {
        $query = $this->createQueryBuilder('a')
            ->select('count(a)');
        return $query->getQuery()->getSingleScalarResult();
    }

    /**
     * {@inheritdoc}
     */
    public function appendJoins(QueryBuilder $queryBuilder, $alias, $locale): void
    {
    }

    /**
     * @param $filters
     * @param $page
     * @param $pageSize
     * @param $limit
     * @param $locale
     * @param $options
     * @return array|object[]
     */
    public function findByFilters($filters, $page, $pageSize, $limit, $locale, $options = []): array
    {
        return $this->getPublishedNews($filters, $locale);
    }

    /**
     * @param $filters
     * @param $locale
     * @return array
     */
    public function getPublishedNews($filters, $locale): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('a')
            ->from(Album::class, 'a')
            ->innerJoin('a.translations', 'translation', Join::WITH, 'translation.locale = :locale')->setParameter('locale', $locale)
            ->where('a.enabled = 1');
        if (isset($filters['sortBy'])) $qb->orderBy($filters['sortBy'], $filters['sortMethod']);

        $albums = $qb->getQuery()->getResult();

        if (!$albums) {
            return [];
        }

        return $albums;
    }
}