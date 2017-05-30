<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Symfony\Component\Asset\VersionStrategy\VersionStrategyInterface;

class GulpRevVersionStrategy implements VersionStrategyInterface
{
    /** @var string */
    private $manifestPath;

    /** @var array */
    private $paths;

    /** @var string */
    private $kernelRootDir;

    /**
     * VersionStrategy constructor.
     *
     * @param string $kernelRootDir
     * @param string $manifestPath
     */
    public function __construct($kernelRootDir, $manifestPath)
    {
        $this->manifestPath  = $manifestPath;
        $this->kernelRootDir = $kernelRootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion($path)
    {
        if (file_exists($path)) {
            return null;
        }

        $path          = pathinfo($this->getAssetVersion($path));
        $filenameParts = explode('-', $path['filename']);

        // With gulp rev, the version is at the end of the filename so it will be the last item of the array
        return $filenameParts[count($filenameParts) - 1];
    }

    /**
     * {@inheritdoc}
     */
    public function applyVersion($path)
    {
        return $this->getAssetVersion($path);
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    private function getAssetVersion($path)
    {
        // The twig extension is a singleton so we store the loaded content into a property to read it only once
        // @see https://knpuniversity.com/screencast/gulp/version-cache-busting#comment-2884388919
        if (count($this->paths) === 0) {
            $this->loadManifestFile();
        }

        if (isset($this->paths[$path])) {
            return $this->paths[$path];
        }

        return $path;
    }

    /**
     * @throws \Exception
     */
    private function loadManifestFile()
    {
        $manifestFilename = basename($this->manifestPath);

        if (! is_file($this->manifestPath)) {
            throw new \Exception(
                sprintf(
                    'Manifest file "%s" not found in path "%s". You can generate this file running gulp',
                    $manifestFilename,
                    $this->manifestPath
                )
            );
        }

        $this->paths = json_decode(file_get_contents($this->manifestPath), true);
    }
}
