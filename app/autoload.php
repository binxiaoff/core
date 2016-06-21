<?php
use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__ . '/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);

// Begin of code for the backward compatibility
spl_autoload_register(function ($sClassName) {
    $sPath = __DIR__ . '/../data/' . $sClassName . '.data.php';
    if (file_exists($sPath)) {
        require_once $sPath;
    }
});
spl_autoload_register(function ($sClassName) {
    $sPath = __DIR__ . '/../data/crud/' . preg_replace('/_crud$/', '', $sClassName) . '.crud.php';
    if (file_exists($sPath)) {
        require_once $sPath;
    }
});
spl_autoload_register(function ($sClassName) {
    $sPath = __DIR__ . '/../librairies/' . $sClassName . '.class.php';
    if (file_exists($sPath)) {
        require_once $sPath;
    }
});
// End of code for the backward compatibility

return $loader;
