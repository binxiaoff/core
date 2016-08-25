<?php
namespace Unilend\Bundle\FrontBundle\Twig;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Symfony\Component\HttpKernel\Kernel;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Symfony\Component\Asset\Packages;
use Unilend\core\Loader;

class FrontBundleExtension extends \Twig_Extension
{
    /** @var Kernel */
    private $kernel;
    /** @var StatisticsManager */
    private $statisticsManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var MemcacheCachePool */
    private $cachePool;
    /** @var LocationManager */
    private $locationManager;
    /** @var Packages */
    private $packages;

    public function __construct(
        Kernel $kernel,
        Packages $assetsPackages,
        StatisticsManager $statisticsManager,
        TranslationManager $translationManager,
        EntityManager $entityManager,
        MemcacheCachePool $cachePool,
        LocationManager $locationManager
    ) {
        $this->kernel             = $kernel;
        $this->packages           = $assetsPackages;
        $this->statisticsManager  = $statisticsManager;
        $this->translationManager = $translationManager;
        $this->entityManager      = $entityManager;
        $this->cachePool          = $cachePool;
        $this->locationManager    = $locationManager;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('setting', array($this, 'settingFunction')),
            new \Twig_SimpleFunction('route', array($this, 'routeFunction')),
            new \Twig_SimpleFunction('svgimage', array($this, 'svgImageFunction')),
            new \Twig_SimpleFunction('__', array($this, 'temporaryTranslateFunction')),
            new \Twig_SimpleFunction('siteurlmedia', array($this, 'completeUrlMediaFunction')),
            new \Twig_SimpleFunction('getCategories', array($this, 'getCategoriesForSvg')),
            new \Twig_SimpleFunction('uploadedImage', array($this, 'uploadedImageFunction')),
            new \Twig_SimpleFunction('getMonths', array($this, 'getMonths')),
            new \Twig_SimpleFunction('photo', array($this, 'photo')),
            new \Twig_SimpleFunction('dictionary', array($this, 'dictionary')),
            new \Twig_SimpleFunction('getStatistic', array($this, 'getStatisticFunction')),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('nbsp', array($this, 'nbspFilter')),
            new \Twig_SimpleFilter('__num', array($this, 'numFilter')),
            new \Twig_SimpleFilter('convertRisk', array($this, 'convertProjectRiskFilter')),
            new \Twig_SimpleFilter('completeProjectImagePath', array($this, 'projectImagePathFilter')),
            new \Twig_SimpleFilter('baseUrl', array($this, 'addBaseUrl')),
            new \Twig_SimpleFilter('countryLabel', array($this, 'getCountry')),
            new \Twig_SimpleFilter('nationalityLabel', array($this, 'getNationality')),
            new \Twig_SimpleFilter('json_decode', array($this, 'jsonDecode'))
        );
    }

    public function settingFunction($name)
    {
        $cachedItem = $this->cachePool->getItem($name);

        if (false === $cachedItem->isHit()) {
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get($name, 'type');
            $value = $settings->value;

            $cachedItem->set($value)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $value;
        } else {
            return $cachedItem->get();
        }
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
        $aSupportedSizes = array(
            'none'    => '',
            'stretch' => 'none',
            'cover'   => 'xMidYMid slice',
            'contain' => 'xMidYMid meet'
        );

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
        $sSvgHtml                 = '<svg role="img"' . $sTitleAttr . $sWidthAttr . $sHeightAttr . $sPreserveAspectRatioAttr . ' class="svg-icon' . $sUseId . '"' . $sSvgHeaders . '>' . $sUses . '</svg>';

        return $sSvgHtml;
    }

    //TODO delete before going live, after having all replaced by path
    public function routeFunction($sRoute)
    {
        return $sRoute;
    }

    //TODO delete before going live
    public function temporaryTranslateFunction($sTranslations)
    {
        return $sTranslations;
    }

    public function completeUrlMediaFunction($sPath)
    {
        return  $this->packages->getUrl('/assets/images/' . $sPath);
    }

    public function getStatisticFunction($statisticType)
    {
        return $this->statisticsManager->getStatistic($statisticType);
    }

    public function getCategoriesForSvg()
    {
        return $this->translationManager->getTranslatedCompanySectorList();
    }

    public function nbspFilter($sString)
    {
        return preg_replace('/[ ](?=[^>]*(?:<|$))/', '&nbsp;', $sString);
    }

    /**
     * @param int|float $number
     * @param int|null  $decimals
     * @return string
     */
    public function numFilter($number, $decimals = null)
    {
        if (is_null($decimals)) {
            $decimals = 2;
        }
        return number_format((float) $number, $decimals, ',', ' ');
    }

    public function convertProjectRiskFilter($sProjectRating)
    {
        return constant('\projects::RISK_' . $sProjectRating);
    }

    public function projectImagePathFilter($image, $size = 'source')
    {
        return  $this->packages->getUrl('/images/dyn/projets/' . $size . '/' . $image);
    }

    public function addBaseUrl($sUrl)
    {
        return  $this->packages->getUrl($sUrl);
    }

    public function getCountry($countryId)
    {
        $countryList = $this->locationManager->getCountries();
        return $countryList[$countryId];
    }

    public function getNationality($nationalityId)
    {
        $nationalityList = $this->locationManager->getNationalities();
        return $nationalityList[$nationalityId];
    }

    public function getMonths()
    {
        $cachedItem = $this->cachePool->getItem('monthsList');

        if (false === $cachedItem->isHit()) {
            /** @var \dates $dates */
            $dates = Loader::loadLib('dates');
            $monthList =  $dates->tableauMois['fr'];

            $cachedItem->set($monthList)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $monthList;
        } else {
            return $cachedItem->get();
        }
    }

    /**
     * @param string $image
     * @param string $format
     * @return string
     */
    public function photo($image, $format = '')
    {
        $photos = new \photos([$this->kernel->getRootDir() . '/../public/default/var/',  $this->packages->getUrl('')]);
        return $photos->display($image, $format);
    }

    /**
     * @param string $section
     * @return array
     */
    public function dictionary($section)
    {
        return $this->translationManager->getAllTranslationsForSection($section);
    }
}
