<?php
namespace Unilend\Bundle\FrontBundle\Twig;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Symfony\Component\Asset\Packages;
use Unilend\core\Loader;

class FrontBundleExtension extends \Twig_Extension
{
    /** @var string */
    private $rootDir;
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
        $rootDir,
        Packages $assetsPackages,
        StatisticsManager $statisticsManager,
        TranslationManager $translationManager,
        EntityManager $entityManager,
        MemcacheCachePool $cachePool,
        LocationManager $locationManager
    ) {
        $this->rootDir            = $rootDir;
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
            new \Twig_SimpleFunction('setting', [$this, 'settingFunction']),
            new \Twig_SimpleFunction('svgimage', [$this, 'svgImageFunction']),
            new \Twig_SimpleFunction('siteurlmedia', [$this, 'completeUrlMediaFunction']),
            new \Twig_SimpleFunction('uploadedImage', [$this, 'uploadedImageFunction']),
            new \Twig_SimpleFunction('getMonths', [$this, 'getMonths']),
            new \Twig_SimpleFunction('photo', [$this, 'photo']),
            new \Twig_SimpleFunction('dictionary', [$this, 'dictionary']),
            new \Twig_SimpleFunction('getStatistic', [$this, 'getStatisticFunction']),
            new \Twig_SimpleFunction('completeTestimonialUrl', [$this, 'completeTestimonialPath'])
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('nbsp', [$this, 'nbspFilter']),
            new \Twig_SimpleFilter('convertRisk', [$this, 'convertProjectRiskFilter']),
            new \Twig_SimpleFilter('completeProjectImagePath', [$this, 'projectImagePathFilter']),
            new \Twig_SimpleFilter('baseUrl', [$this, 'addBaseUrl']),
            new \Twig_SimpleFilter('countryLabel', [$this, 'getCountry']),
            new \Twig_SimpleFilter('nationalityLabel', [$this, 'getNationality']),
            new \Twig_SimpleFilter('json_decode', [$this, 'jsonDecode'])
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

    public function completeUrlMediaFunction($sPath)
    {
        return  $this->packages->getUrl('/assets/images/' . $sPath);
    }

    public function getStatisticFunction($statisticType, $date = null)
    {
        $requestedDate = (is_null($date)) ? new \DateTime('NOW') : new \DateTime($date);
        return $this->statisticsManager->getStatistic($statisticType, $requestedDate);
    }

    public function nbspFilter($sString)
    {
        return preg_replace('/[ ](?=[^>]*(?:<|$))/', '&nbsp;', $sString);
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
        $photos = new \photos([$this->rootDir . '/../public/default/var/',  $this->packages->getUrl('')]);
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

    public function completeTestimonialPath($name, $type)
    {
        return $this->packages->getUrl('/var/testimonials/' . $type . '/' . $name);
    }
}
