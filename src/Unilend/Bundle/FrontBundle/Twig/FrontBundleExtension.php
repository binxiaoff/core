<?php
namespace Unilend\Bundle\FrontBundle\Twig;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Symfony\Component\Asset\Packages;
use Unilend\core\Loader;

class FrontBundleExtension extends \Twig_Extension
{
    /** @var string $url */
    private $url;
    /** @var StatisticsManager */
    private $statisticsManager;
    /** @var TranslationManager */
    private $translationManager;
    /** @var EntityManager */
    private $entityManager;
    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(
        Packages $assetsPackages,
        StatisticsManager $statisticsManager,
        TranslationManager $translationManager,
        EntityManager $entityManager,
        MemcacheCachePool $cachePool
    ) {
        $this->url                = $assetsPackages->getUrl('');
        $this->statisticsManager  = $statisticsManager;
        $this->translationManager = $translationManager;
        $this->entityManager      = $entityManager;
        $this->cachePool          = $cachePool;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('setting', array($this, 'settingFunction')),
            new \Twig_SimpleFunction('route', array($this, 'routeFunction')),
            new \Twig_SimpleFunction('svgimage', array($this, 'svgImageFunction')),
            new \Twig_SimpleFunction('__', array($this, 'temporaryTranslateFunction')),
            new \Twig_SimpleFunction('siteurlmedia', array($this, 'completeUrlMediaFunction')),
            new \Twig_SimpleFunction('getStatistics', array($this, 'getStatistics')),
            new \Twig_SimpleFunction('getCategories', array($this, 'getCategoriesForSvg')),
            new \Twig_SimpleFunction('uploadedImage', array($this, 'uploadedImageFunction')),
            new \Twig_SimpleFunction('getCountries', array($this, 'getCountries')),
            new \Twig_SimpleFunction('getNationalities', array($this, 'getNationalities')),
            new \Twig_SimpleFunction('getMonths', array($this, 'getMonths'))
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
            new \Twig_SimpleFilter('nationalityLabel', array($this, 'getNationality'))
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
        return $this->url . '/var/images/' . $image;
    }

    public function getName()
    {
        return 'app_extension';
    }

    public function svgImageFunction($sId, $sTitle, $iWidth, $iHeight, $sSizing = null)
    {
        $sUrl        = $this->url . '/bundles/unilendfront/images/svg/icons.svg';
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
        return $this->url . '/bundles/unilendfront/images/' . $sPath;
    }

    public function getStatistics()
    {
        return $this->statisticsManager->getAllStatistics();
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

    public function projectImagePathFilter($sImage)
    {
        return $this->url . '/images/dyn/projets/72/' . $sImage;
    }

    public function addBaseUrl($sUrl)
    {
        return $this->url . $sUrl;
    }

    public function getCountries()
    {
        /** @var \pays_v2 $countries */
        $countries = $this->entityManager->getRepository('pays_v2');
        /** @var array $countyList */
        $countyList = [];

        foreach ($countries->select('', 'ordre ASC') as $country) {
            $countyList[$country['id_pays']] = $country['fr'];
        }

        return $countyList;

        $cachedItem = $this->cachePool->getItem('countryList');


        if (false === $cachedItem->isHit()) {
            /** @var \pays_v2 $countries */
            $countries = $this->entityManager->getRepository('pays_v2');
            /** @var array $countyList */
            $countyList = [];

            foreach ($countries->select('', 'ordre ASC') as $country) {
                $countyList[$country['id_pays']] = $country['fr'];
            }

            $cachedItem->set($countyList)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $countyList;
        } else {
            return $cachedItem->get();
        }
    }

    public function getNationalities()
    {
        /** @var \nationalites_v2 $nationalities */
        $nationalities = $this->entityManager->getRepository('nationalites_v2');
        /** @var array $nationalityList */
        $nationalityList = [];

        foreach ($nationalities->select('', 'ordre ASC') as $nationality) {
            $nationalityList[$nationality['id_nationalite']] = $nationality['fr_f'];
        }


        return $nationalityList;


        $cachedItem = $this->cachePool->getItem('nationalityList');

        if (false === $cachedItem->isHit()) {

            /** @var \nationalites_v2 $nationalities */
            $nationalities = $this->entityManager->getRepository('nationalites_v2');
            /** @var array $nationalityList */
            $nationalityList = [];

            foreach ($nationalities->select('', 'ordre ASC') as $nationality) {
                $nationalityList[$nationality['id_nationalite']] = $nationality['fr_f'];
            }

            $cachedItem->set($nationalityList)->expiresAfter(3600);
            $this->cachePool->save($cachedItem);

            return $nationalityList;
        } else {
            return $cachedItem->get();
        }
    }

    public function getCountry($countryId)
    {
        $countryList = $this->getCountries();
        return $countryList[$countryId];
    }

    public function getNationality($nationalityId)
    {
        $nationalityList = $this->getNationalities();
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



}