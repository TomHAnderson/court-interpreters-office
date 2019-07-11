<?php
/**
 * module/InterpretersOffice/Entity/Repository/UserRepository.php
 */
declare(strict_types=1);

namespace InterpretersOffice\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use InterpretersOffice\Entity;
use InterpretersOffice\Service\ProperNameParsingTrait;

use Zend\Paginator\Paginator as ZendPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator as DoctrineAdapter;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
/**
 * UserRepository
 *
 *
 */
class UserRepository extends EntityRepository
{


    use ResultCachingQueryTrait;

    use ProperNameParsingTrait;

     /**
     * @var string cache id
     */
    protected $cache_id = 'users';

    /**
     * cache lifetime
     *
     * @var int
     */
    protected $cache_lifetime = 3600;

    /**
     * cache
     *
     * @var CacheProvider
     */
    protected $cache;

    /**
     * constructor
     *
     * @param \Doctrine\ORM\EntityManager  $em    The EntityManager to use.
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class The class descriptor.
     */
    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->cache = $em->getConfiguration()->getResultCacheImpl();
        $this->cache->setNamespace('users');
    }

    /**
     * finds a submitter based on email
     *
     * @param  string $email
     * @return InterpretersOffice\Entity\User|null
     */
    public function findSubmitterByEmail(string $email) : ? Entity\User
    {

        $dql = 'SELECT u, p FROM InterpretersOffice\Entity\User u JOIN u.person p '
        . ' JOIN u.role r WHERE p.email = :email AND r.name = :role';
        return $this->createQuery($dql)->setParameters(
            ['email' => $email,'role' => 'submitter']
        )
            ->getOneOrNullResult();
    }


    /**
     * Gets count of event|requests created|modified by User.
     *
     * For deciding whether Users can modify their Hat through the
     * profile-update feature.
     *
     * @param Entity\User $user
     * @throws \RuntimeException
     * @return Array
     */
    public function countRelatedEntities(Entity\User $user) : Array
    {
        $person_id = $user->getPerson()->getId();
        $role = (string)$user->getRole();
        if ($role != 'submitter') {
            throw new \RuntimeException(
                __FUNCTION__. " can only be used for User entities whose role is 'submitter'"
            );
        }
        $dql = 'SELECT COUNT(r.id) requests,
        (SELECT COUNT(e.id) FROM InterpretersOffice\Entity\Event e
            JOIN e.submitter s WHERE s.id = :person_id
        ) events
        FROM InterpretersOffice\Requests\Entity\Request r JOIN r.submitter p
        JOIN r.modifiedBy m WHERE p.id = :person_id OR (m.person = p and p.id = :person_id)';
        $params = [':person_id'=>$person_id];
        $result = $this->createQuery($dql)
            ->useResultCache(false)
            ->setParameters([':person_id'=>$person_id])
            ->getResult();

        return $result;
    }

    /**
     * Counts existing uses of $email
     *
     * For use with UserForm and user/profile action.
     *
     * @param Entity\User $user
     * @param string $email
     * @return int
     */
    public function countExistingUserEmail(Entity\User $user, string $email) : int
    {
        $dql = 'SELECT COUNT(p.id) FROM '.Entity\Person::class
        . ' p JOIN '.Entity\User::class
        . ' u WITH u.person = p WHERE p.email = :email AND p.id <> :id';
        $person = $user->getPerson();
        $query = $this->createQuery($dql)->useResultCache(false)
        ->setParameters(['email'=> $email,'id'   => $person->getId(),]);

        return (int)$query->getSingleScalarResult();
    }


    /**
     * autocompletion for admin/users name-or-email
     *
     * @param string $name_or_email
     * @param array $options
     * @return array
     */
    public function autocomplete(string $name_or_email, Array $options) : array
    {
        if ('name' == $options['search_by']) {
            $name = $this->parseName($name_or_email);
            $parameters = ['lastname' => "$name[last]%"];
            if ($name['first']) {
                $parameters['firstname'] = "$name[first]%";
            }
        } elseif ('email' == $options['search_by']) {
            $parameters['email'] = $name_or_email.'%';
        } else {
            throw new \InvalidArgumentException(sprintf(
                '"search_by" option must be one of "name" or "email", got option: (%s) %s',
                gettype($options['search_by']),
                is_object($options['search_by']) ? 'instance of '.get_class($options['search_by']) :
                    print_r($options['search_by'],true)
            ));
        }

        $qb = $this->createQueryBuilder('u')->join('u.person','p');
        $qb->select([
            $qb->expr()->concat(
                'p.lastname',$qb->expr()->literal(', '), 'p.firstname'
            ). 'AS label',
            'u.id AS value',
        ]);
        if (isset($parameters['email'])) {
            $qb->where('p.email LIKE :email');
        } else { // search by name
            $qb->where('p.lastname LIKE :lastname');
            if ($name['first']) {
                $qb->andWhere('p.firstname LIKE :firstname');
            }
        }
        $qb->setParameters($parameters)
        ->setMaxResults(20);

        return $data = $qb->getQuery()->getResult();
    }

    /*
    SELECT COUNT(r.id) requests,
    (SELECT COUNT(e.id) FROM InterpretersOffice\Entity\Event e
    JOIN e.submitter s WHERE s.id = 1476
    ) events
    FROM InterpretersOffice\Requests\Entity\Request r JOIN r.submitter p
    JOIN r.modifiedBy m WHERE p.id = 1476 OR (m.person = p and p.id = 1476)
     */
}
