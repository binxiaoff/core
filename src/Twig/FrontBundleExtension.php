<?php

namespace Unilend\Twig;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Asset\Packages;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Unilend\CacheKeys;
use Unilend\Entity\{Pays, Projects};
use Unilend\Service\{LocationManager, Simulator\EntityManager, StatisticsManager, Translation\TranslationManager};

class FrontBundleExtension extends AbstractExtension
{
    /** @var string */
    private $publicDirectory;
    /** @var StatisticsManager */
    private $statisticsManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var CacheItemPoolInterface */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;
    /** @var Packages */
    private $packages;

    public function __construct(
        string $publicDirectory,
        Packages $assetsPackages,
        StatisticsManager $statisticsManager,
        TranslationManager $translationManager,
        EntityManager $entityManager,
        CacheItemPoolInterface $cachePool,
        LocationManager $locationManager
    ) {
        $this->publicDirectory    = $publicDirectory;
        $this->packages           = $assetsPackages;
        $this->statisticsManager  = $statisticsManager;
        $this->translationManager = $translationManager;
        $this->entityManager      = $entityManager;
        $this->cachePool          = $cachePool;
        $this->locationManager    = $locationManager;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('setting', [$this, 'settingFunction']),
            new TwigFunction('svgimage', [$this, 'svgImageFunction']),
            new TwigFunction('uploadedImage', [$this, 'uploadedImageFunction']),
            new TwigFunction('photo', [$this, 'photo']),
            new TwigFunction('dictionary', [$this, 'dictionary']),
            new TwigFunction('getStatistic', [$this, 'getStatisticFunction']),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('nbsp', [$this, 'nbspFilter']),
            new TwigFilter('convertRisk', [$this, 'convertProjectRiskFilter']),
            new TwigFilter('completeProjectImagePath', [$this, 'projectImagePathFilter']),
            new TwigFilter('baseUrl', [$this, 'addBaseUrl']),
            new TwigFilter('countryLabel', [$this, 'getCountry']),
            new TwigFilter('nationalityLabel', [$this, 'getNationality']),
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
        ];
    }

    public function settingFunction($name)
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

    public function uploadedImageFunction($image)
    {
        return $this->packages->getUrl('/var/images/' . $image);
    }

    public function getName()
    {
        return 'app_extension';
    }

    public function svgImageFunction($sId, $sTitle, $iWidth, $iHeight, $sSizing = null)
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
        if (false === isset($sSizing) || false === in_array($sSizing, $aSupportedSizes)) {
            $sSizing = 'contain';
        }

        //TODO implement the possibility to use several SVGs, which as for today is not used in the code, except for the tests
        //TODO add possibility to call without ID if necessary, for instance all calls are made with id

        $sUseId                   = str_replace('#', '', $sId);
        $sUses                    = '<use xlink:href="' . $sUrl . $sId . '" class="svg-file-' . $sUseId . '"/>';
        $sTitleAttr               = (isset($sTitle) ? ' title="' . $sTitle . '"' : '');
        $sWidthAttr               = (isset($iWidth) ? ' width="' . $iWidth . '"' : '');
        $sHeightAttr              = (isset($iHeight) ? ' height="' . $iHeight . '"' : '');
        $sPreserveAspectRatioAttr = (isset($sSizing) ? ' preserveAspectRatio="' . $aSupportedSizes[$sSizing] . '"' : '');

        return '<svg role="img"' . $sTitleAttr . $sWidthAttr . $sHeightAttr . $sPreserveAspectRatioAttr
            . ' class="svg-icon svg-icon-' . $sUseId . '"' . $sSvgHeaders . '>' . $sUses . '</svg>';
    }

    public function getStatisticFunction($statisticType, $date = null)
    {
        $requestedDate = (null === $date) ? new \DateTime('NOW') : new \DateTime($date);

        return $this->statisticsManager->getStatistic($statisticType, $requestedDate);
    }

    public function nbspFilter($sString)
    {
        return preg_replace('/[ ](?=[^>]*(?:<|$))/', '&nbsp;', $sString);
    }

    public function convertProjectRiskFilter($sProjectRating)
    {
        return constant(Projects::class . '::RISK_' . $sProjectRating);
    }

    public function projectImagePathFilter($image, $size = 'source')
    {
        return $this->packages->getUrl('/images/dyn/projets/' . $size . '/' . $image);
    }

    public function addBaseUrl($sUrl)
    {
        return $this->packages->getUrl($sUrl);
    }

    public function getCountry($countryId)
    {
        if (empty($countryId)) {
            $countryId = Pays::COUNTRY_FRANCE;
        }

        $countryList = $this->locationManager->getCountries();

        return $countryList[$countryId] ?? '';
    }

    public function getNationality($nationalityId)
    {
        if (empty($nationalityId)) {
            $nationalityId = Pays::COUNTRY_FRANCE;
        }

        $nationalityList = $this->locationManager->getNationalities();

        return $nationalityList[$nationalityId];
    }

    /**
     * @param string $image
     * @param string $format
     *
     * @return string
     */
    public function photo($image, $format = '')
    {
        $photos = new \photos([$this->publicDirectory . 'var/', $this->packages->getUrl('')]);

        return $photos->display($image, $format);
    }

    /**
     * @param string $section
     *
     * @return array
     */
    public function dictionary($section)
    {
        return $this->translationManager->getAllTranslationsForSection($section);
    }
}
