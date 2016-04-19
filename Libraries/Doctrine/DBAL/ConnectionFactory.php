<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Unilend\Libraries\Doctrine\DBAL;

/**
 * Connection
 */
class ConnectionFactory
{
    /**
     * Create a connection by name.
     *
     * @param array $params
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function createConnection(array $params)
    {
        $connection = new DataBase($params);

        return $connection;
    }
}
