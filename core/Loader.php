<?php

namespace Unilend\core;

use Doctrine\DBAL\{Connection as DoctrineConnection, DBALException, DriverManager};
use Symfony\Component\Yaml\Yaml;
use Unilend\Doctrine\DBAL\Connection as UnilendConnection;

class Loader
{
    /** @var DoctrineConnection */
    private static $connection;

    /**
     * @param string                 $object
     * @param array                  $params
     * @param UnilendConnection|null $db
     *
     * @throws DBALException
     *
     * @return object|bool
     *
     * @internal you cannot call this method directly
     */
    public static function loadData(string $object, array $params = [], ?UnilendConnection $db = null)
    {
        if (null === $db) {
            $db = self::getConnection();
        }

        $object = '\\' . $object;

        return new $object($db, $params);
    }

    /**
     * @param string $object
     *
     * @return bool
     */
    public static function crudExists(string $object): bool
    {
        $path = realpath(__DIR__ . '/..') . '/data/crud/' . $object . '.crud.php';

        return file_exists($path);
    }

    /**
     * @param string $table
     *
     * @throws DBALException
     *
     * @return bool
     */
    public static function generateCrud(string $table): bool
    {
        $db     = self::getConnection();
        $path   = realpath(__DIR__ . '/..') . '/';
        $result = $db->query('DESC ' . $table);

        if ($result) {
            $nb_cle = 0;
            while ($record = $db->fetch_assoc($result)) {
                if ('PRI' === $record['Key']) {
                    ++$nb_cle;
                }
            }

            $result = $db->query('DESC ' . $table);

            $slug           = false;
            $declaration    = '';
            $initialisation = '';
            $remplissage    = '';
            $escapestring   = '';
            $updatefields   = '';
            $clist          = '';
            $cvalues        = '';
            $id             = [];
            while ($record = $db->fetch_assoc($result)) {
                $declaration    .= '    public $' . $record['Field'] . ";\r\n";
                $initialisation .= '        $this->' . $record['Field'] . " = '';\r\n";
                $remplissage    .= '            $this->' . $record['Field'] . " = \$record['" . $record['Field'] . "'];\r\n";
                $escapestring   .= '        $this->' . $record['Field'] . ' = $this->bdd->escape_string($this->' . $record['Field'] . ");\r\n";

                if ('PRI' === $record['Key']) {
                    $id[] = $record['Field'];
                }
                if ('PRI' !== $record['Key'] && 'updated' !== $record['Field']) {
                    $updatefields .= '`' . $record['Field'] . "`=\"'.\$this->" . $record['Field'] . ".'\",";
                } elseif ('updated' === $record['Field']) {
                    $updatefields .= '`' . $record['Field'] . '` = NOW(),';
                }

                if ('slug' === $record['Field']) {
                    $slug = true;
                }

                //Si la clé primaire est unique, c'est un autoincrémente donc on l'exclus de la liste
                if (1 === $nb_cle) {
                    if ('PRI' !== $record['Key']) {
                        $clist .= '`' . $record['Field'] . '`,';
                    }

                    if ('PRI' !== $record['Key'] && 'updated' !== $record['Field'] && 'added' !== $record['Field'] && 'hash' !== $record['Field']) {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ('updated' === $record['Field'] || 'added' === $record['Field']) {
                        $cvalues .= 'NOW(),';
                    } elseif ('hash' === $record['Field']) {
                        $cvalues .= 'MD5(UUID()),';
                    }
                } else {
                    $clist .= '`' . $record['Field'] . '`,';

                    if ('updated' !== $record['Field'] && 'added' !== $record['Field'] && 'hash' !== $record['Field']) {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ('updated' === $record['Field'] || 'added' === $record['Field']) {
                        $cvalues .= 'NOW(),';
                    } elseif ('hash' === $record['Field']) {
                        $cvalues .= 'md5(UUID()),';
                    }
                }
            }

            $updatefields = mb_substr($updatefields, 0, mb_strlen($updatefields) - 1);
            $clist        = mb_substr($clist, 0, mb_strlen($clist) - 1);
            $cvalues      = mb_substr($cvalues, 0, mb_strlen($cvalues) - 1);

            if (1 === $nb_cle) {
                $dao = file_get_contents($path . 'core/crud.sample.php');

                $controleslug      = '';
                $controleslugmulti = '';

                $dao = str_replace('--controleslug--', $controleslug, $dao);
                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            } else {
                $dao = file_get_contents($path . 'core/crud2.sample.php');

                if ($slug) {
                    $controleslugmulti = "\$this->bdd->controlSlugMultiLn('--table--', \$this->slug, \$this->--id--, \$list_field_value, \$this->id_langue);";
                } else {
                    $controleslugmulti = '';
                }

                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            }

            if (isset($id[0])) {
                $dao = str_replace('--id--', $id[0], $dao);
            }

            $dao = str_replace('--declaration--', $declaration, $dao);
            $dao = str_replace('--initialisation--', $initialisation, $dao);
            $dao = str_replace('--remplissage--', $remplissage, $dao);
            $dao = str_replace('--escapestring--', $escapestring, $dao);
            $dao = str_replace('--updatefields--', $updatefields, $dao);
            $dao = str_replace('--clist--', $clist, $dao);
            $dao = str_replace('--cvalues--', $cvalues, $dao);
            $dao = str_replace('--table--', $table, $dao);
            $dao = str_replace('--classe--', $table . '_crud', $dao);

            touch($path . 'data/crud/' . $table . '.crud.php');
            $c = fopen($path . 'data/crud/' . $table . '.crud.php', 'r+b');

            fputs($c, $dao);
            fclose($c);

            return true;
        }

        return false;
    }

    /**
     * @param string $library
     *
     * @return object|bool
     */
    public static function loadLib($library)
    {
        $sProjectPath = realpath(__DIR__ . '/..') . '/';
        $sClassPath   = '';
        $aPath        = explode('/', $library);

        if (count($aPath) > 1) {
            $library    = array_pop($aPath);
            $sClassPath = implode('/', $aPath) . '/';
        }

        if (false === file_exists($sProjectPath . 'librairies/' . $sClassPath . $library . '.class.php')) {
            return false;
        }

        $sClassName = '\\' . $library;

        return new $sClassName();
    }

    /**
     * @throws DBALException
     *
     * @return DoctrineConnection
     */
    private static function getConnection(): DoctrineConnection
    {
        if (self::$connection instanceof DoctrineConnection) {
            return self::$connection;
        }

        $params = Yaml::parseFile(dirname(__DIR__) . '/config/services.yaml');

        return self::$connection = DriverManager::getConnection([
            'url' => $_SERVER['DATABASE_URL'] . '&driverClass=' . $params['parameters']['dbal_driver_class'] . '&wrapperClass=' . $params['parameters']['dbal_wrapper_class'],
        ]);
    }
}
