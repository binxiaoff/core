<?php

declare(strict_types=1);

namespace Unilend\Doctrine\DBAL;

use Doctrine\DBAL\Driver\Statement;
use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection as BaseConnection;

class Connection extends BaseConnection
{
    /**
     * Executes a statement.
     *
     * @return \Doctrine\DBAL\Statement
     */
    public function query()
    {
        $stmt = null;
        $args = func_get_args();

        try {
            switch (count($args)) {
                case 1:
                    $stmt = parent::query($args[0]);

                    break;
                case 2:
                    $stmt = parent::query($args[0], $args[1]);

                    break;
                case 3:
                    $stmt = parent::query($args[0], $args[1], $args[2]);

                    break;
                case 4:
                    $stmt = parent::query($args[0], $args[1], $args[2], $args[3]);

                    break;
                default:
                    $stmt = parent::query();
            }
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);

            return false;
        }

        return $stmt;
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param Statement|bool|null $statement
     *
     * @deprecated for backwards compatibility only
     *
     * @return mixed
     */
    public function fetch_array($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->fetch(\PDO::FETCH_BOTH);
        }

        return [];
    }

    /**
     * @param Statement|bool|null $statement
     *
     * @deprecated for backwards compatibility only
     *
     * @return mixed
     */
    public function fetch_assoc($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @param Statement|bool|null $statement
     *
     * @deprecated for backwards compatibility only
     *
     * @return mixed
     */
    public function num_rows($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->rowCount();
        }

        return 0;
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param string $string
     *
     * @deprecated for backwards compatibility only
     *
     * @throws \Exception
     *
     * @return string
     */
    public function escape_string($string)
    {
        $search  = ['\\', "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ['\\\\', '\\0', '\\n', '\\r', "\\'", '\"', '\\Z'];

        return str_replace($search, $replace, $string);
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @deprecated for backwards compatibility only
     *
     * @throws \Exception
     *
     * @return string
     */
    public function insert_id()
    {
        return $this->lastInsertId();
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @deprecated for backwards compatibility only
     *
     * @param Statement|bool|null $statement
     * @param int                 $row       not used, for backwards compatibility only
     * @param int                 $column
     *
     * @return mixed
     */
    public function result($statement, $row = 0, $column = 0)
    {
        if ($statement instanceof Statement) {
            return $statement->fetchColumn($column);
        }

        return null;
    }

    public function generateSlug($string)
    {
        $string = strip_tags($string);

        return \URLify::filter($string);
    }
}
