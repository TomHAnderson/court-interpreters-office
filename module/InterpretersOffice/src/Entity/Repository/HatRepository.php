<?php
/**
 *  module/InterpretersOffice/src/Entity/Repository/HatRepository.php.
 */

namespace InterpretersOffice\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use InterpretersOffice\Entity\Hat;

/**
 * Hat repository.
 *
 * @author david
 */
class HatRepository extends EntityRepository
{
    use ResultCachingQueryTrait;

    /**
     * the cache id
     *
     * @var string $cache_id
     */
    protected $cache_id = 'hats';

    /**
     * returns Hat entities for Person form's select element.
     *
     * excludes the Hat entities that correspond to subtypes of the
     * Person entity -- Judge and Interpreter -- whose hat-types are constrained
     * to certain values.
     *
     * @return array
     */
    public function getHatsForPersonForm()
    {
        // this returns "AUSA", "defense attorney", etc as well as
        // the usually-anonymous "Magistrate" and "Pretrial" unless
        $dql = 'SELECT h FROM InterpretersOffice\Entity\Hat h '
           .' WHERE h.name NOT LIKE \'%court interpreter%\' AND h.name <> \'Judge\''
           .' AND h.role IS NULL AND h.anonymity <> 1';
        $query = $this->createQuery($dql);

        return $query->getResult();
    }

    /**
     * gets array of options for populating forms
     *
     * @return Array $options
     */
    public function getAnonymityOptions()
    {
        return [
            ['label' => 'never','value' => Hat::ANONYMITY_NEVER],
            ['label' => 'always','value' => Hat::ANONYMITY_ALWAYS],
            ['label' => 'optional','value' => Hat::ANONYMITY_OPTIONAL],
        ];
    }

     /**
     * figures out how to populate Hat select menu based on the action and the
     * role of the current user, to comply with ACL rules
      *
     * @param string $auth_user_role role of currently authenticated user
     * @param string $action current action: either 'create' or 'update'
     * @return array
     */
    public function getHatsForUserForm($auth_user_role, $action)
    {
        if (! in_array($action, ['create','update'])) {
            throw new \DomainException('invalid action parameter');
        }
        $dql = 'SELECT h, r FROM InterpretersOffice\Entity\Hat h JOIN h.role r ';
        switch ($auth_user_role) {
            case 'staff':
                break;
            case 'anonymous': // e.g., user registration
            case 'submitter':
                $dql .= 'WHERE r.name = \'submitter\'';
                break;
            case 'manager':
                    $dql .= 'WHERE r.name IN (\'submitter\' , \'staff\') '
                    . ' OR h.name = \'Interpreters Office staff\' ';
                break;
            case 'administrator':
               // nothing more to do
                break;
            default:
                throw new \RuntimeException(sprintf(
                    '%s: unsupported auth user role %s',
                    __METHOD__,
                    $auth_user_role
                ));
        }
        $dql .= ' ORDER BY h.name';
        $query = $this->createQuery($dql);

        return $query->getResult();
    }

    /**
     * get Hats for InterpreterFieldset.
     *
     * @return array
     */
    public function getInterpreterHats()
    {
        $query = $this->createQuery(
            'SELECT h  FROM InterpretersOffice\Entity\Hat h WHERE h.name '
                . 'LIKE :what ORDER BY h.name'
        )->setParameters([':what' => '%court interpreter']);

        return $query->getResult();
    }
    /**
     * gets a Hat by its name.
     *
     * we use this instead of findOneBy() for the sake of caching. overriding
     * findOneBy appears to be a bit too much work.
     * //http://stackoverflow.com/questions/28668093/how-to-cache-doctrine-findoneby-query-with-cache-id-and-cache-lifetime-optio
     * @param string $name
     * @return InterpretersOffice\Entity\Hat
     */
    public function getHatByName($name)
    {
        return $query = $this->createQuery(
            'SELECT h  FROM InterpretersOffice\Entity\Hat h '
                . 'WHERE h.name = :what ORDER BY h.name'
        )->setParameters([':what' => $name])->setMaxResults(1)->getOneorNullResult();
    }

    /**
     * fetches all the hats
     *
     * @return array
     */
    public function findAll()
    {
        return $this->createQuery('SELECT h, r FROM InterpretersOffice\Entity\Hat h
            LEFT JOIN h.role r
            ORDER BY h.name')
            ->getResult();
    }

    /**
     * gets Hat options
     *
     * @param  array $anonymity array of Hat class constants
     * @return array of key-value pairs for populating a form
     */
    public function getHatOptions(array $anonymity = null)
    {
        $dql = 'SELECT h.id value, h.name label FROM ' . Hat::class . ' h';
        $q = $this->createQuery('');
        if ($anonymity !== null) {
            $dql .= ' WHERE h.anonymity IN (:anonymity)';
            $q->setParameters(['anonymity' => $anonymity]);
        }
        $dql .= ' ORDER BY h.name';
        return $q->setDql($dql)->getResult();
    }
}
