<?php
namespace Unilend\Bundle\Doctrine\DBAL;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Driver\Statement;

class DBAL
{
    /** @var Connection Current connection */
    private static $connection;

    /** @var string Last SQL command */
    public static $sql;

    /**
     * DataBase constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        self::connect($config);
    }

    /********************* Connections handling *********************/
    /**
     * Creates a new Connection object and connects it to specified database.
     *
     * @param array $config
     *
     * @return Connection
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function connect($config = [])
    {
        self::$connection = DriverManager::getConnection($config);
    }

    /**
     * Disconnects from database (doesn't destroy Connection object).
     * @return void
     */
    public static function disconnect()
    {
        self::getConnection()->close();
    }


    /**
     * Returns TRUE when connection was established.
     * @return bool
     */
    public static function isConnected()
    {
        try {
            $connected = self::getConnection()->isConnected();
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            $connected = false;
        }
        return $connected;
    }

    /**
     *
     * @return Connection
     * @throws \Exception
     */
    public static function getConnection()
    {
        if (self::$connection === null) {
            throw new \Exception('Not connected to database.');
        }

        return self::$connection;

    }

    /**
     * @param Connection $connection
     *
     * @return Connection
     */
    public static function setConnection(Connection $connection)
    {
        return self::$connection = $connection;
    }

    /********************* Backwards compatibility methods *********************/

    /**
     * Executes a statement
     *
     * @param string $statement
     *
     * @deprecated for backwards compatibility only.
     *
     * @return \Doctrine\DBAL\Statement
     */
    public static function query($statement)
    {
        self::$sql = $statement;
        try {
            return self::getConnection()->executeQuery($statement);
        } catch (\Exception $exception) {
            trigger_error($exception->getMessage(), E_USER_WARNING);
            return false;
        }

    }

    /**
     * Fetches the next row from a result set
     *
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
     *
     * @return mixed
     */
    public static function fetch_array($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->fetch(\PDO::FETCH_BOTH);
        } else {
            return array();
        }
    }

    /**
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
     *
     * @return mixed
     */
    public static function fetch_assoc($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->fetch(\PDO::FETCH_ASSOC);
        } else {
            return array();
        }
    }

    /**
     * Returns the number of rows affected by the last SQL statement
     *
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
     *
     * @return mixed
     */
    public static function num_rows($statement)
    {
        if ($statement instanceof Statement) {
            return $statement->rowCount();
        } else {
            return 0;
        }
    }

    /**
     * Escapes a string for use in a query.
     *
     * @param string $string
     *
     * @deprecated for backwards compatibility only.
     *
     * @return string
     *
     * @throws \Exception
     */
    public static function escape_string($string)
    {
        $search  = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
        $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");

        return str_replace($search, $replace, $string);
    }

    /**
     * Returns the ID of the last inserted row or sequence value
     *
     * @deprecated for backwards compatibility only.
     *
     * @return string
     * @throws \Exception
     */
    public static function insert_id()
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Returns a single column from the next row of a result set
     *
     * @deprecated for backwards compatibility only.
     *
     * @param Statement|null|boolean $statement
     * @param int       $row not used, for backwards compatibility only.
     * @param int       $column
     *
     * @return mixed
     */
    public static function result($statement, $row = 0, $column = 0)
    {
        if ($statement instanceof Statement) {
            return $statement->fetchColumn($column);
        }
        return null;
    }

    public static function controlSlug($table, $slug, $id_name, $id_value)
    {
        $params    = [
            'table' => $table,
            'slug'  => $slug,
            'colum' => $id_name,
            'value' => $id_value
        ];
        $statement = self::getConnection()->executeQuery('SELECT slug FROM :table WHERE slug = :slug AND :colum != :value', $params);

        if ($statement->rowCount() == 1 || $slug == "") {
            if ($table == 'tree' && $id_value == 1 && $slug == '') {
                $slug = '';
            } else {
                $slug = $slug . '-' . $id_value;
            }
        }

        self::getConnection()->update($table, ['slug' => $slug], [$id_name => $id_value]);
    }

    public static function controlSlugMulti($table, $slug, $id_value, $list_field_value, $id_langue)
    {
        $list = '';
        foreach ($list_field_value as $champ => $valeur) {
            $list .= ' ' . $champ . ' != "' . $valeur . '" ';
            if (next($list_field_value)) {
                $list .= ' OR ';
            }
        }

        $sql = 'SELECT * FROM ' . $table . ' WHERE slug = "' . $slug . '" AND (' . $list . ') ';

        $res = self::query($sql);

        if (self::num_rows($res) >= 1) {
            $slug = $slug . '-' . $id_value;

            if ($id_langue != '') {
                $slug .= '-' . $id_langue;
            }

            $list2 = '';
            foreach ($list_field_value as $champ => $valeur) {
                $list2 .= ' AND ' . $champ . ' = "' . $valeur . '" ';
            }

            $sql = 'UPDATE ' . $table . ' SET slug = "' . $slug . '" WHERE 1=1 ' . $list2 . ' ';
            self::query($sql);

            self::controlSlugMulti($table, $slug, $id_value, $list_field_value, $id_langue);
        }
    }

    public function controlSlugMultiLn($table, $slug, $id_value, $list_field_value, $id_langue)
    {
        $res = self::query('SELECT * FROM ' . $table . ' WHERE slug = "' . $slug . '" AND id_langue = "' . $id_langue . '"');

        if (self::num_rows($res) > 1) {
            $new_slug = $slug;

            if ($id_langue != '') {
                $new_slug .= '-' . $id_langue;
            }

            $new_slug .= '-' . $id_value;

            $list2 = '';
            foreach ($list_field_value as $champ => $valeur) {
                $list2 .= ' AND ' . $champ . ' = "' . $valeur . '" ';
            }

            self::query('UPDATE ' . $table . ' SET slug = "' . $new_slug . '" WHERE 1=1 ' . $list2);
            self::controlSlugMultiLn($table, $new_slug, $id_value, $list_field_value, $id_langue);
        }
    }

    public function generateSlug($string)
    {
        $string = strip_tags(utf8_decode($string));
        $string = strtr($string, 'ÀÁÂÃÄÅÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝÇçàáâãäåèéêëìíîïòóôõöùúûüýÿÑñ', 'AAAAAAEEEEIIIIOOOOOUUUUYCcaaaaaaeeeeiiiiooooouuuuyyNn');
        $string = strtolower($string); // lower-case the string
        $string = preg_replace('/[ ]/', '-', $string); // replace special characters by score
        $string = preg_replace('/[^a-z0-9-.]/', '', $string); // replace all non-alphanumeric characters by void
        $string = preg_replace('/[-]{2,}/', '-', $string); // replace multi '-' by once
        $string = preg_replace('/[-]{1,}$/', '', $string); // replace end '-' by void
        return $string;
    }

    public function listEnum($nom_table, $nom_enum, $nom_champ, $selected = null)
    {
        $resultat = self::query('SHOW COLUMNS FROM ' . $nom_table . ' LIKE "' . $nom_enum . '"'); //or die("show columns from $nom_table like '$nom_enum' ".mysql_error());

        while ($result = self::fetch_array($resultat)) {
            if (preg_match('!enum(.+)!', $result['Type'])) {
                $enum2 = preg_replace('!^enum\((.+)\)$!', '$1', $result['Type']);
                $enum1 = str_replace("'", "", $enum2);
                $enum  = explode(',', $enum1);

                $selecteur = '<select name="' . $nom_champ . '" id="' . $nom_champ . '" class="select">';

                foreach ($enum as $valeur) {
                    if ($selected == $valeur) {
                        $selecteur .= ' <option selected value="' . $valeur . '">' . $valeur . '</option>';
                    } else {
                        $selecteur .= ' <option value="' . $valeur . '">' . $valeur . '</option>';
                    }
                }

                $selecteur .= '</select>';
            }
        }

        return $selecteur;
    }

    public function getEnum($nom_table, $nom_enum)
    {
        $sql       = 'SHOW COLUMNS FROM ' . $nom_table . ' LIKE "' . $nom_enum . '" ';
        $resultat  = self::query($sql);
        $data      = self::fetch_assoc($resultat);
        $new_enum2 = preg_replace('!^enum\((.+)\)$!', '$1', $data['Type']);
        $new_enum1 = str_replace("'", "", $new_enum2);
        $new_enum  = explode(',', $new_enum1);
        return $new_enum;
    }

    public function majEnum($nom_table, $nom_enum, $valeur)
    {
        $sql       = 'SHOW COLUMNS FROM ' . $nom_table . ' LIKE "' . $nom_enum . '" ';
        $resultat  = self::query($sql);
        $data      = self::fetch_assoc($resultat);
        $new_enum2 = preg_replace('!^enum\((.+)\)$!', '$1', $data['Type']) . ",'" . $valeur . "'";
        $new_enum1 = str_replace("'", "", $new_enum2);
        $new_enum  = explode(',', $new_enum1);
        $enum_tab  = array();
        foreach ($new_enum as $enum) {
            if ($enum != '') {
                $enum_tab[] = $enum;
            }
        }
        $new_enum = implode('\',\'', $enum_tab);
        $sql      = 'ALTER TABLE `' . $nom_table . '` CHANGE `' . $nom_enum . '` `' . $nom_enum . '` ENUM(\'' . $new_enum . '\') NULL DEFAULT NULL';

        self::query($sql);
    }

    public function deleteEnum($nom_table, $nom_enum, $valeur)
    {
        $sql       = 'SHOW COLUMNS FROM ' . $nom_table . ' LIKE "' . $nom_enum . '" ';
        $resultat  = self::query($sql);
        $data      = self::fetch_assoc($resultat);
        $new_enum2 = preg_replace('!^enum\((.+)\)$!', '$1', $data['Type']) . ",'" . $valeur . "'";
        $new_enum1 = str_replace("'", "", $new_enum2);
        $new_enum  = explode(',', $new_enum1);
        $enum_tab  = array();
        foreach ($new_enum as $enum) {
            if ($enum != $valeur) {
                $enum_tab[] = $enum;
            }
        }
        $new_enum = implode('\',\'', $enum_tab);
        $sql      = 'ALTER TABLE `' . $nom_table . '` CHANGE `' . $nom_enum . '` `' . $nom_enum . '` ENUM(\'' . $new_enum . '\') NULL DEFAULT NULL';
        self::query($sql);
    }

    /**
     * @param string $statement
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function run($statement)
    {
        return self::getConnection()->executeQuery($statement)->fetchAll();
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     *
     * @param Statement $statement
     */
    public function free_result(Statement $statement)
    {
        $statement->closeCursor();
    }
}