<?php

/**
 * Image resizer
 * If an image does not exists in corresponding directory, we resize it from source
 * Resulting image is saved in order to avoid regenerating it next time
 *
 * Current restrictions are
 * - Image format is JPEG (source and generated images)
 * - File extension is '.jpg'
 * - Compression is 90%
 * - Fallback image is always the same, even if we know expected size
 */
include __DIR__ . '/../../core/errorhandler.class.php';
include __DIR__ . '/../../config.php';

/**
 * @var array $config
 */
$handler = new ErrorHandler(
    $config['error_handler'][$config['env']]['file'],
    $config['error_handler'][$config['env']]['allow_display'],
    $config['error_handler'][$config['env']]['allow_log'],
    $config['error_handler'][$config['env']]['report']
);

try {
    if (1 !== preg_match('#images/dyn/([^/]+)/([0-9]+)/(.+\.(jpg|jpeg|png))#i', $_SERVER['REQUEST_URI'], $aMatches)) {
        throw new \ResizableImageException('URL does not match pattern');
    }

    if (! isset($config['images'][$aMatches[1]])) {
        $oImagick = new \Imagick($config['static_path'][$config['env']] . 'images/dyn/default.jpg');
        throw new \ResizableImageException('Unknown image type');
    }

    $sImageType       = $aMatches[1];
    $iImageHeight     = $aMatches[2];
    $iImageWidth      = round($iImageHeight * $config['images'][$sImageType]['width'] / $config['images'][$sImageType]['height']);
    $sFileName        = $aMatches[3];
    $sTypeRootPath    = $config['static_path'][$config['env']] . 'images/dyn/' . $sImageType;
    $sSourceImagePath = $sTypeRootPath . '/source/' . $sFileName;

    if (false === file_exists($sSourceImagePath)) {
        $oImagick = new \Imagick($config['static_path'][$config['env']] . 'images/dyn/default.jpg');
        $oImagick->scaleImage($iImageWidth, $iImageHeight);
        throw new \ResizableImageException('Unable to find source image');
    }

    if (false === is_dir($sTypeRootPath . '/' . $iImageHeight)) {
        mkdir($sTypeRootPath . '/' . $iImageHeight);
    }

    $oImagick = new \Imagick($sSourceImagePath);
    $oImagick->scaleImage($iImageWidth, $iImageHeight);
    $oImagick->setImageFormat('jpg');
    $oImagick->setImageCompression(Imagick::COMPRESSION_JPEG);
    $oImagick->setImageCompressionQuality(90);
    $oImagick->stripImage();
    $oImagick->writeImage($sTypeRootPath . '/' . $iImageHeight . '/' . $sFileName);
} catch (\Exception $oException) {
    if (! isset($oImagick)) {
        $oImagick = new \Imagick($config['static_path'][$config['env']] . 'images/dyn/default.jpg');
    }
}

header('Content-Type: image/jpeg');
echo $oImagick;

class ResizableImageException extends \Exception
{
}
