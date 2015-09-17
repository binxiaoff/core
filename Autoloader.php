<?php

class Autoloader
{

    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'), true);
    }

    /**
     * Inclue le fichier correspondant à notre classe
     * @param $class string Le nom de la classe à charger
     */
    public static function autoload($class)
    {
        $aSearch = array('Unilend\\','\\');
        $aReplace = array('',DIRECTORY_SEPARATOR);
        $sPathClass = str_replace($aSearch, $aReplace, $class);

        require_once $sPathClass . '.php';
    }
}