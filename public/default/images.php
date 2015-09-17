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
if ($config['error_handler'][$config['env']]['activate']) {
    $handler = new ErrorHandler(
        $config['error_handler'][$config['env']]['file'],
        $config['error_handler'][$config['env']]['allow_display'],
        $config['error_handler'][$config['env']]['allow_log'],
        $config['error_handler'][$config['env']]['report']
    );
}

try {
    if (1 !== preg_match('#images/([^/]+)/([^/]+)/([^/]+)\.jpg#', $_SERVER['REQUEST_URI'], $aMatches)) {
        throw new \ResizableImageException('URL does not match pattern');
    }

    if (!isset($config['images'][$aMatches[1]])) {
        throw new \ResizableImageException('Unknown image type');
    }

    $sImageType = $aMatches[1];

    if (!isset($config['images'][$sImageType]['formats'][$aMatches[2]])) {
        throw new \ResizableImageException('Unknown format');
    }

    $sImageFormat     = $aMatches[2];
    $sFileName        = $aMatches[3];
    $sTypeRootPath    = $config['static_path'][$config['env']] . 'images/' . $sImageType;
    $sSourceImagePath = $sTypeRootPath . '/source/' . $sFileName . '.jpg';

    if (false === file_exists($sSourceImagePath)) {
        throw new \ResizableImageException('Unable to find source image');
    }

    if (false === is_dir($sTypeRootPath . '/' . $sImageFormat)) {
        mkdir($sTypeRootPath . '/' . $sImageFormat);
    }

    $oImagick = new \Imagick($sSourceImagePath);
    $oImagick->scaleImage($config['images'][$sImageType]['formats'][$sImageFormat]['width'], $config['images'][$sImageType]['formats'][$sImageFormat]['height']);
    $oImagick->setImageFormat('jpg');
    $oImagick->setImageCompression(Imagick::COMPRESSION_JPEG);
    $oImagick->setImageCompressionQuality(90);
    $oImagick->stripImage();
    $oImagick->writeImage($sTypeRootPath . '/' . $sImageFormat . '/' . $sFileName . '.jpg');
} catch (\Exception $oException) {
    $oImagick = new \Imagick($config['static_path'][$config['env']] . 'images/default.jpg');
}

header('Content-Type: image/jpeg');
echo $oImagick;

class ResizableImageException extends \Exception
{
}
