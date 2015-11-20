<?php

require __DIR__ .'/vendor/autoload.php';

class Autoloader
{
    public static function register()
    {
        spl_autoload_register(function($sClassName) {
            $sPath = __DIR__ . '/' . str_replace(array('Unilend\\', '\\'), array('', DIRECTORY_SEPARATOR), $sClassName) . '.php';
            if (file_exists($sPath)) {
                require_once $sPath;
            }
        });
        spl_autoload_register(function($sClassName) {
            $sPath = __DIR__ . '/data/' . $sClassName . '.data.php';
            if (file_exists($sPath)) {
                require_once $sPath;
            }
        });
        spl_autoload_register(function($sClassName) {
            $sPath = __DIR__ . '/data/crud/' . preg_replace('/_crud$/', '', $sClassName) . '.crud.php';
            if (file_exists($sPath)) {
                require_once $sPath;
            }
        });
        spl_autoload_register(function($sClassName) {
            $sPath = __DIR__ . '/librairies/' . $sClassName . '.class.php';
            if (file_exists($sPath)) {
                require_once $sPath;
            }
        });
    }
}
