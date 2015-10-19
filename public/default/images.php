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
include_once __DIR__ . '/../../core/errorhandler.class.php';
include_once __DIR__ . '/../../config.php';

/**
 * @var array $config
 */
$handler = new \ErrorHandler(
    $config['error_handler'][$config['env']]['file'],
    $config['error_handler'][$config['env']]['allow_display'],
    $config['error_handler'][$config['env']]['allow_log'],
    $config['error_handler'][$config['env']]['report']
);

try {
    if (1 !== preg_match('#images/dyn/([^/]+)/([0-9]+)/(.+\.(jpg|jpeg|png))#i', $_SERVER['REQUEST_URI'], $aMatches)) {
        throw new ResizableImageException('URL does not match pattern');
    }

    if (! isset($config['images'][$aMatches[1]])) {
        throw new ResizableImageException('Unknown image type');
    }

    $sImageType       = $aMatches[1];
    $iImageHeight     = $aMatches[2];
    $iImageWidth      = round($iImageHeight * $config['images'][$sImageType]['width'] / $config['images'][$sImageType]['height']);
    $sFileName        = $aMatches[3];
    $sTypeRootPath    = $config['static_path'][$config['env']] . 'images/dyn/' . $sImageType;
    $sSourceImagePath = $sTypeRootPath . '/source/' . $sFileName;

    if (false === in_array($iImageHeight, $config['images'][$sImageType]['authorized'])) {
        throw new ResizableImageException('Unauthorized image size');
    }

    if (false === file_exists($sSourceImagePath)) {
        $oImagick = new \Imagick($config['static_path'][$config['env']] . 'images/dyn/default.jpg');
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
