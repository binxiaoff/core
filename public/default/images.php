<?php

/**
 * Image resizer
 * If an image does not exists in corresponding directory, we resize it from source
 * Resulting image is saved in order to avoid regenerating it next time
 *
 * Current restrictions are
 * - Input file extension is 'jpg, jpeg, JPG, JPEG, png, PNG'
 * - Output format is JPEG
 * - Output compression is 90%
 */
$loader = require __DIR__ . '/../../app/autoload.php';
require_once __DIR__ . '/../../app/AppKernel.php';
include_once __DIR__ . '/../../config.php';

error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

$kernel = new AppKernel('prod', false);
$kernel->boot();

$errorLogfile = $kernel->getLogDir() . '/error.' . date('Ymd') . '.log';
\Unilend\core\ErrorHandler::enable($errorLogfile);


$config = $kernel->getContainer()->getParameter('image_resize');

try {
    if (1 !== preg_match('#images/dyn/([^/]+)/([0-9]+)/(.+\.(jpg|jpeg|png))$#i', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), $aMatches)) {
        throw new ResizableImageException('URL does not match pattern');
    }

    if (! isset($config[$aMatches[1]])) {
        throw new ResizableImageException('Unknown image type');
    }

    $sStaticPath      = $kernel->getContainer()->getParameter('path.static');
    $sImageType       = $aMatches[1];
    $iImageHeight     = $aMatches[2];
    $iImageWidth      = round($iImageHeight * $config[$sImageType]['width'] / $config[$sImageType]['height']);
    $sFileName        = $aMatches[3];
    $sTypeRootPath    = $sStaticPath . 'images/dyn/' . $sImageType;
    $sSourceImagePath = $sTypeRootPath . '/source/' . $sFileName;

    if (false === in_array($iImageHeight, $config[$sImageType]['authorized'])) {
        throw new ResizableImageException('Unauthorized image size');
    }

    if (false === file_exists($sSourceImagePath)) {
        $oImagick = new \Imagick($sStaticPath . 'images/dyn/default.jpg');
        $oImagick->scaleImage($iImageWidth, $iImageHeight);

        throw new ResizableImageException('Unable to find source image');
    }

    if (false === is_dir($sTypeRootPath . '/' . $iImageHeight)) {
        mkdir($sTypeRootPath . '/' . $iImageHeight);
    }

    $oImagick = new \Imagick($sSourceImagePath);
    $oImagick->scaleImage($iImageWidth, $iImageHeight);
    $oImagick->setImageFormat('jpg');
    $oImagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
    $oImagick->setImageCompressionQuality(90);
    $oImagick->stripImage();
    $oImagick->writeImage($sTypeRootPath . '/' . $iImageHeight . '/' . $sFileName);
} catch (\Exception $oException) {
    if (! isset($oImagick)) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
        header('Status: 404 Not Found');
        exit;
    }
}

header('Content-Type: image/jpeg');
echo $oImagick;

class ResizableImageException extends \Exception
{
}
