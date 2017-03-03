<?php

/** module/InterpretersOffice/src/Entity/LanguageRepository.php */

namespace InterpretersOffice\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Zend\Paginator\Paginator as ZendPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Doctrine\ORM\EntityManagerInterface;

/**
 * custom EntityRepository class for Language entity.
 */
class LanguageRepository extends EntityRepository  implements CacheDeletionInterface

{
    use ResultCachingQueryTrait;
    
    /**
     * @var string cache id prefix
     */
    protected $cache_id_prefix = 'languages:';
    
    /**
     * returns all languages wrapped in a paginator.
     *
     * @param int $page
     *
     * @return ZendPaginator
     */
    public function findAllWithPagination($page = 1)
    {
        $dql = 'SELECT language FROM InterpretersOffice\Entity\Language language ORDER BY language.name';
        $query = $this->createQuery($dql,
                $this->cache_id_prefix . 'findAll'
            )->setMaxResults(30);

        $adapter = new DoctrineAdapter(new ORMPaginator($query));
        $paginator = new ZendPaginator($adapter);
        if (!count($paginator)) {
            return null;
        }
        $paginator->setCurrentPageNumber($page)
            ->setItemCountPerPage(30);

        return $paginator;
    }
    
    /**
     * gets all the languages ordered by name ascending.
     *
     * @return array of all our LocationType objects
     */
    public function findAll()
    {
        // have the decency to sort them by name ascending, 
        // and use the result cache
        $query = $this->createQuery(
            'SELECT l FROM InterpretersOffice\Entity\Language l ORDER BY l.name ASC',
            $this->cache_id_prefix . 'findAll'
        );
        
        return $query->getResult();
    }

    /**
     * experimental 
     * 
     * implements cache deletion
     * @param string $cache_id
     */
    public function deleteCache($cache_id = null) {

        $cache = $this->getEntityManager()->getConfiguration()->getResultCacheImpl();
        $cache->delete($this->cache_id_prefix . 'findAll');
    }
}
