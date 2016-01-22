<?php
namespace Unilend\core;

/**
 * Class Loader
 * @package Unilend\core
 */
class Loader
{
    public static function loadData($object, $params = array(), $db = '')
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        if ($db == '') {
            $db = new \bdd($config['bdd_config'][$config['env']], $config['bdd_option'][$config['env']]);
        }

        $path = $config['path'][$config['env']];

        //On regarde si la classe mere existe, si elle n'existe pas, on la genere
        if (!file_exists($path . 'data/crud/' . $object . '.crud.php')) {
            //generation de la classe mere
            if (!self::generateCRUD($object, $db, $path)) {
                return false;
            }
        }

        //On regarde si la classe fille existe, si elle n'existe pas, on la genere
        if (!file_exists($path . 'data/' . $object . '.data.php')) {
            //generation de la classe mere
            self::generateDATA($object, $db, $path);
        }

        $object = '\\' . $object;

        return new $object($db, $params);
    }

    //Genere un fichier CRUD a partir d'une table
    private static function generateCRUD($table, $db, $path)
    {
        //On recupere la structure de la table
        $sql    = "desc " . $table;
        $result = $db->query($sql);

        if ($result) {
            //On compte le nombre de cle primaire
            $nb_cle = 0;
            while ($record = $db->fetch_array($result)) {
                if ($record['Key'] == 'PRI') {
                    $nb_cle++;
                }
            }

            //On recupere la structure de la table
            $sql    = "desc " . $table;
            $result = $db->query($sql);

            //initialisation
            $slug           = false;
            $declaration    = '';
            $initialisation = '';
            $remplissage    = '';
            $escapestring   = '';
            $updatefields   = '';
            $clist          = '';
            $cvalues        = '';
            $id             = array();
            while ($record = $db->fetch_array($result)) {
                $declaration .= "\tpublic \$" . $record['Field'] . ";\r\n";
                $initialisation .= "\t\t\$this->" . $record['Field'] . " = '';\r\n";
                $remplissage .= "\t\t\t\$this->" . $record['Field'] . " = \$record['" . $record['Field'] . "'];\r\n";
                $escapestring .= "\t\t\$this->" . $record['Field'] . " = \$this->bdd->escape_string(\$this->" . $record['Field'] . ");\r\n";

                //On stock les clé primaire dans un tableau

                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }
                if ($record['Key'] != 'PRI' && $record['Field'] != 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=\"'.\$this->" . $record['Field'] . ".'\",";
                } elseif ($record['Field'] == 'updated') {
                    $updatefields .= "`" . $record['Field'] . "`=NOW(),";
                }

                //On check si il y a un slug present dans les champs
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

            //chargement du sample en fonction du nombre de clé primaires
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
        } else {
            return false;
        }
    }

    //Genere un fichier DATA a partir d'une table
    private static function generateDATA($table, $db, $path)
    {
        $sql    = "desc " . $table;
        $result = $db->query($sql);

        if ($result) {
            $id = array();
            while ($record = $db->fetch_array($result)) {
                if ($record['Key'] == 'PRI') {
                    $id[] = $record['Field'];
                }
            }

            //si la clé primaire est unique
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
        } else {
            return false;
        }
    }

    public static function loadService($sService, $aParams = array())
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        $sPath = $config['path'][$config['env']];

        $sService = trim($sService, "/ \t\n\r\0\x0B");
        if (!file_exists($sPath . 'Service/' . $sService . '.php')) {
            return false;
        } else {
            $sService = str_replace('/', '\\', $sService);
            $sClassName = 'Unilend\Service\\' . $sService;
            return new $sClassName($aParams);
        }
    }


    public static function loadLib($sLibrary, $aParams = array(), $bInstancing = true)
    {
        /* @var array $config */
        include __DIR__ . '/../config.php';

        $sProjectPath = $config['path'][$config['env']];

        $sClassPath = '';
        $aPath      = explode("/", $sLibrary);
        if (count($aPath) > 1) {
            $sLibrary = array_pop($aPath);
            $sClassPath = implode("/", $aPath) . '/';
        }
        if (!file_exists($sProjectPath . 'librairies/' . $sClassPath . $sLibrary . '.class.php')) {
            return false;
        } else {
            if ($bInstancing) {
                $sClassName = '\\' . $sLibrary;
                return new $sClassName($aParams);
            }
        }
    }
}