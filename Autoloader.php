<?php

class Autoloader
{

    static function register()
    {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Inclue le fichier correspondant à notre classe
     * @param $class string Le nom de la classe à charger
     */
    static function autoload($class)
    {
        $sPathClass = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        //Into librairies
        require $sPathClass . '.php';
    }
}