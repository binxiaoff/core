<?php
/**
 * Created by PhpStorm.
 * User: binxiao
 * Date: 03/03/2017
 * Time: 17:45
 */

namespace Unilend\Bundle\CoreBusinessBundle\Repository;


use Doctrine\ORM\EntityRepository;

class PartnerProjectAttachmentRepository extends EntityRepository
{
    public function getAllTypes()
    {
        $qb = $this->createQueryBuilder('ppa');
        $qb->groupBy('ppa.idAttachmentType');

        return $qb->getQuery()->getResult();
    }
}
