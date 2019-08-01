<?php

declare(strict_types=1);

namespace Unilend\Twig;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Unilend\CacheKeys;
use Unilend\Service\{Simulator\EntityManager, Translation\TranslationManager};

class FrontBundleExtension extends AbstractExtension
{
    /** @var TranslationManager */
    private $translationManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var Packages */
    private $packages;

    /**
     * @param Packages               $assetsPackages
     * @param TranslationManager     $translationManager
     * @param EntityManager          $entityManager
     * @param CacheItemPoolInterface $cachePool
     */
    public function __construct(
        Packages $assetsPackages,
        TranslationManager $translationManager,
        EntityManager $entityManager,
        CacheItemPoolInterface $cachePool
    ) {
        $this->packages           = $assetsPackages;
        $this->translationManager = $translationManager;
        $this->entityManager      = $entityManager;
        $this->cachePool          = $cachePool;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('setting', [$this, 'settingFunction']),
            new TwigFunction('svgimage', [$this, 'svgImageFunction']),
            new TwigFunction('dictionary', [$this, 'dictionary']),
        ];
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return mixed
     */
    public function settingFunction(string $name)
    {
        $cachedItem = $this->cachePool->getItem($name);

        if (false === $cachedItem->isHit()) {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get($name, 'type');
            $value = $settings->value;

            $cachedItem->set($value)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);

            return $value;
        }

        return $cachedItem->get();
    }

    /**
     * @param string      $id
     * @param string      $title
     * @param int         $width
     * @param int         $height
     * @param string|null $sizing
     *
     * @return string
     */
    public function svgImageFunction(string $id, string $title, int $width, int $height, ?string $sizing = null): string
    {
        $sUrl        = $this->packages->getUrl('images/svg/icons.svg', 'gulp');
        $sSvgHeaders = ' version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve"';

        // Supported sizing sizes, using preserveAspectRatio
        $aSupportedSizes = [
            'none'    => '',
            'stretch' => 'none',
            'cover'   => 'xMidYMid slice',
            'contain' => 'xMidYMid meet',
        ];

        // Fallback to 'contain' aspect ratio if invalid option given
        if (false === isset($sizing) || false === in_array($sizing, $aSupportedSizes)) {
            $sizing = 'contain';
        }

        //TODO implement the possibility to use several SVGs, which as for today is not used in the code, except for the tests
        //TODO add possibility to call without ID if necessary, for instance all calls are made with id

        $sUseId                   = str_replace('#', '', $id);
        $sUses                    = '<use xlink:href="' . $sUrl . $id . '" class="svg-file-' . $sUseId . '"/>';
        $sTitleAttr               = (isset($title) ? ' title="' . $title . '"' : '');
        $sWidthAttr               = (isset($width) ? ' width="' . $width . '"' : '');
        $sHeightAttr              = (isset($height) ? ' height="' . $height . '"' : '');
        $sPreserveAspectRatioAttr = (isset($sizing) ? ' preserveAspectRatio="' . $aSupportedSizes[$sizing] . '"' : '');

        return '<svg role="img"' . $sTitleAttr . $sWidthAttr . $sHeightAttr . $sPreserveAspectRatioAttr
            . ' class="svg-icon svg-icon-' . $sUseId . '"' . $sSvgHeaders . '>' . $sUses . '</svg>';
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function dictionary($section): array
    {
        return $this->translationManager->getAllTranslationsForSection($section);
    }
}
