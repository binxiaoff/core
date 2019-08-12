<?php
namespace Unilend\Doctrine\DBAL;

use Facile\DoctrineMySQLComeBack\Doctrine\DBAL\Connection as BaseConnection;
use Doctrine\DBAL\Driver\Statement;

class Connection extends BaseConnection
{
    /**
     * Executes a statement
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
     * Fetches the next row from a result set
     *
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
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
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
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
     * Returns the number of rows affected by the last SQL statement
     *
     * @param Statement|null|boolean $statement
     *
     * @deprecated for backwards compatibility only.
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
     * @deprecated for backwards compatibility only.
     *
     * @return string
     *
     * @throws \Exception
     */
    public function escape_string($string)
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
    public function insert_id()
    {
        return $this->lastInsertId();
    }

    /**
     * Returns a single column from the next row of a result set
     *
     * @deprecated for backwards compatibility only.
     *
     * @param Statement|null|boolean $statement
     * @param int                    $row not used, for backwards compatibility only.
     * @param int                    $column
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

    public function controlSlug($table, $slug, $idName, $idValue)
    {
        $params    = [
            'slug'  => $slug,
            'value' => $idValue
        ];
        $statement = $this->executeQuery('SELECT slug FROM ' . $table . ' WHERE slug = :slug AND ' . $idName . ' != :value', $params);

        if ($statement->rowCount() == 1 || $slug == "") {
            if ($table == 'tree' && $idValue == 1 && $slug == '') {
                $slug = '';
            } else {
                $slug = $slug . '-' . $idValue;
            }
        }

        $sql = 'UPDATE ' . $table . ' SET slug = "' . $slug . '" WHERE ' . $idName .' = '. $idValue;
        $this->query($sql);
    }

    public function controlSlugMultiLn($table, $slug, $id_value, $list_field_value, $id_langue)
    {
        $res = $this->query('SELECT * FROM ' . $table . ' WHERE slug = "' . $slug . '" AND id_langue = "' . $id_langue . '"');

        if ($this->num_rows($res) > 1) {
            $new_slug = $slug;

            if ($id_langue != '') {
                $new_slug .= '-' . $id_langue;
            }

            $new_slug .= '-' . $id_value;

            $list2 = '';
            foreach ($list_field_value as $champ => $valeur) {
                $list2 .= ' AND ' . $champ . ' = "' . $valeur . '" ';
            }

            $this->query('UPDATE ' . $table . ' SET slug = "' . $new_slug . '" WHERE 1=1 ' . $list2);
            $this->controlSlugMultiLn($table, $new_slug, $id_value, $list_field_value, $id_langue);
        }
    }

    public function generateSlug($string)
    {
        $string = strip_tags($string);
        $string = \URLify::filter($string);

        return $string;
    }

    public function listEnum($nom_table, $nom_enum, $nom_champ, $selected = null)
    {
        $resultat = $this->query('SHOW COLUMNS FROM ' . $nom_table . ' LIKE "' . $nom_enum . '"'); //or die("show columns from $nom_table like '$nom_enum' ".mysql_error());

        while ($result = $this->fetch_array($resultat)) {
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

    /**
     * @param string $statement
     *
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function run($statement)
    {
        return $this->executeQuery($statement)->fetchAll();
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
