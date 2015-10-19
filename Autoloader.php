<?php
require __DIR__ .'/vendor/autoload.php';

class Autoloader
{

    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Inclue le fichier correspondant à notre classe
     * @param $class string Le nom de la classe à charger
     */
    public static function autoload($class)
    {
        $aSearch = array('Unilend\\', '\\');
        $aReplace = array('', DIRECTORY_SEPARATOR);
        require_once str_replace($aSearch, $aReplace, $class) . '.php';
    }


}
