<?php

namespace Unilend\Bridge\Doctrine\ORM;

use Doctrine\ORM\Query\SqlWalker;

class UsePrimaryKeyForInnerJoinWalker extends SqlWalker
{
    /**
     * {@inheritdoc}
     */
    public function walkJoin($join)
    {
        $sql = parent::walkJoin($join);

        return preg_replace('/(INNER JOIN .+) ON/i', '$1 USE INDEX (`PRIMARY`) ON', $sql);
    }
}
