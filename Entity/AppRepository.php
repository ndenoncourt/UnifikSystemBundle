<?php

namespace Unifik\SystemBundle\Entity;

use Unifik\SystemBundle\Lib\BaseEntityRepository;

/**
 * App repository
 */
class AppRepository extends BaseEntityRepository
{
    const BACKEND_APP_ID = 1;
    const FRONTEND_APP_ID = 2;

    public function findFirstOneExcept($exceptId)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.id <> :exceptId')
            ->setParameter('exceptId', $exceptId)
            ->orderBy('a.ordering', 'ASC')
            ->setMaxResults(1);

        return $this->processQuery($qb, true);
    }

    public function findAllExcept($exceptId)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.id <> :exceptId')
            ->setParameter('exceptId', $exceptId)
            ->orderBy('a.ordering', 'ASC');

        return $this->processQuery($qb);
    }

    public function findAllHasAccess($authChecker = null, $userId = null)
    {
        $qb = $this->createQueryBuilder('a');
        if ($authChecker !== null && !$authChecker->isGranted('ROLE_BACKEND_ADMIN')) {
            $qb->innerJoin('a.sections', 's')
                ->innerJoin('s.roles', 'sr')
                ->innerJoin('sr.users', 'ru')
                ->where('ru.id = :userId')
                ->setParameter('userId', $userId);
        }
        $qb->orderBy('a.ordering', 'ASC');
        return $this->processQuery($qb);
    }
}
