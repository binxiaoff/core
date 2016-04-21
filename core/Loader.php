<?php
namespace Unilend\core;

/**
 * Class Loader
 * @package Unilend\core
 */
class Loader
{
    /**
     * @internal You should not call this method directly.
     */
    public static function loadData($object, array $params = array(), $db = null)
    {
        $config = self::loadConfig();

        if (null === $db) {
            $db = \bdd::instance($config['bdd_config'][$config['env']], $config['bdd_option'][$config['env']]);
        }

        $path = $config['path'][$config['env']];

        if (false === file_exists($path . 'data/crud/' . $object . '.crud.php') && false === self::generateCRUD($object, $db, $path)
            || false === file_exists($path . 'data/' . $object . '.data.php') && false === self::generateDATA($object, $db, $path)) {
            return false;
        }

        $object = '\\' . $object;

        return new $object($db, $params);
    }

    private static function generateCRUD($table, $db, $path)
    {
        $result = $db->query('DESC ' . $table);

        if ($result) {
            $nb_cle = 0;
            while ($record = $db->fetch_array($result)) {
                if ($record['Key'] == 'PRI') {
                    $nb_cle++;
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
            $id             = array();
            while ($record = $db->fetch_assoc($result)) {
                $declaration .= "\tpublic \$" . $record['Field'] . ";\r\n";
                $initialisation .= "\t\t\$this->" . $record['Field'] . " = '';\r\n";
                $remplissage .= "\t\t\t\$this->" . $record['Field'] . " = \$record['" . $record['Field'] . "'];\r\n";
                $escapestring .= "\t\t\$this->" . $record['Field'] . " = \$this->bdd->escape_string(\$this->" . $record['Field'] . ");\r\n";

                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }
                if ($record['Key'] != 'PRI' && $record['Field'] != 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=\"'.\$this->" . $record['Field'] . ".'\",";
                } elseif ($record['Field'] == 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=NOW(),";
                }

                if ($record['Field'] == 'slug') {
                    $slug = true;
                }

                //Si la clé primaire est unique, c'est un autoincrémente donc on l'exclus de la liste
                if ($nb_cle == 1) {
                    if ($record['Key'] != 'PRI') {
                        $clist .= "`" . $record['Field'] . "`,";
                    }

                    if ($record['Key'] != 'PRI' && $record['Field'] != 'updated' && $record['Field'] != 'added' && $record['Field'] != 'hash') {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ($record['Field'] == 'updated' || $record['Field'] == 'added') {
                        $cvalues .= "NOW(),";
                    } elseif ($record['Field'] == 'hash') {
                        $cvalues .= "md5(UUID()),";
                    }
                } else {
                    $clist .= "`" . $record['Field'] . "`,";

                    if ($record['Field'] != 'updated' && $record['Field'] != 'added' && $record['Field'] != 'hash') {
                        $cvalues .= "\"'.\$this->" . $record['Field'] . ".'\",";
                    } elseif ($record['Field'] == 'updated' || $record['Field'] == 'added') {
                        $cvalues .= "NOW(),";
                    } elseif ($record['Field'] == 'hash') {
                        $cvalues .= "md5(UUID()),";
                    }
                }
            }

            $updatefields = substr($updatefields, 0, strlen($updatefields) - 1);
            $clist        = substr($clist, 0, strlen($clist) - 1);
            $cvalues      = substr($cvalues, 0, strlen($cvalues) - 1);

            if ($nb_cle == 1) {
                $dao = file_get_contents($path . 'core/crud.sample.php');

                if ($slug) {
                    $controleslug      = "\$this->bdd->controlSlug('--table--',\$this->slug,'--id--',\$this->--id--);";
                    $controleslugmulti = "\$this->bdd->controlSlugMultiLn('--table--',\$this->slug,\$this->--id--,\$list_field_value,\$this->id_langue);";
                } else {
                    $controleslug      = "";
                    $controleslugmulti = "";
                }

                $dao = str_replace('--controleslug--', $controleslug, $dao);
                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            } else {
                $dao = file_get_contents($path . 'core/crud2.sample.php');

                if ($slug) {
                    $controleslugmulti = "\$this->bdd->controlSlugMultiLn('--table--',\$this->slug,\$this->--id--,\$list_field_value,\$this->id_langue);";
                } else {
                    $controleslugmulti = "";
                }

                $dao = str_replace('--controleslugmulti--', $controleslugmulti, $dao);
            }

            $dao = str_replace('--id--', $id[0], $dao);
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
            chmod($path . 'data/crud/' . $table . '.crud.php', 0766);
            $c = fopen($path . 'data/crud/' . $table . '.crud.php', 'r+');

            fputs($c, $dao);
            fclose($c);

            return true;
        }
        return false;
    }

    private static function generateDATA($table, $db, $path)
    {
        $result = $db->query('DESC ' . $table);

        if ($result) {
            $id = array();
            while ($record = $db->fetch_assoc($result)) {
                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }
            }

            if (count($id) == 1) {
                $dao = file_get_contents($path . 'core/data.sample.php');
            } else {
                $dao = file_get_contents($path . 'core/data2.sample.php');
            }

            $dao = str_replace('--table--', $table, $dao);
            $dao = str_replace('--classe--', $table, $dao);
            $dao = str_replace('--id--', $id[0], $dao);

            touch($path . 'data/' . $table . '.data.php');
            chmod($path . 'data/' . $table . '.data.php', 0766);
            $c = fopen($path . 'data/' . $table . '.data.php', 'r+');

            fputs($c, $dao);
            fclose($c);

            return true;
        }
        return false;
    }

    /**
     * @deprecated Limit its usage (Use it only in Services). Use Controller::get() instead. It will be removed with the project new infrastructure.
     */
    public static function loadService($sService, array $aParams = array())
    {
        $config = self::loadConfig();
        $sPath  = $config['path'][$config['env']];

        $sService = trim($sService, "/ \t\n\r\0\x0B");
        if (false === file_exists($sPath . 'Service/' . $sService . '.php')) {
            return false;
        }
        $sService   = str_replace('/', '\\', $sService);
        $sClassName = 'Unilend\Service\\' . $sService;
        return new $sClassName($aParams);
    }

    public static function loadLib($sLibrary, array $aParams = array(), $bInstancing = true)
    {
        $config       = self::loadConfig();
        $sProjectPath = $config['path'][$config['env']];
        $sClassPath   = '';
        $aPath        = explode('/', $sLibrary);

        if (count($aPath) > 1) {
            $sLibrary   = array_pop($aPath);
            $sClassPath = implode('/', $aPath) . '/';
        }

        if (false === file_exists($sProjectPath . 'librairies/' . $sClassPath . $sLibrary . '.class.php')) {
            return false;
        } elseif ($bInstancing) {
            $sClassName = '\\' . $sLibrary;
            return new $sClassName($aParams);
        }
    }

    /**
     * @deprecated It will be removed with the project new infrastructure.
     * @return array
     */
    public static function loadConfig()
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';
        return $config;
    }
}
