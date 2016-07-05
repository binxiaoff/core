<?php
namespace Unilend\Bundle\FrontBundle\Twig;

use Cache\Adapter\Memcache\MemcacheCachePool;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;
use Symfony\Component\Asset\Packages;

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

    public function __construct(Packages $assetsPackages, StatisticsManager $statisticsManager, TranslationManager $translationManager, EntityManager $entityManager, MemcacheCachePool $cachePool)
    {
        $this->url = $assetsPackages->getUrl('');
        $this->statisticsManager = $statisticsManager;
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
            new \Twig_SimpleFunction('getCategories', array($this, 'getCategoriesForSvg'))
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('nbsp', array($this, 'nbspFilter')),
            new \Twig_SimpleFilter('__num', array($this, 'numFilter')),
            new \Twig_SimpleFilter('convertRisk', array($this, 'convertProjectRiskFilter')),
            new \Twig_SimpleFilter('completeProjectImagePath', array($this, 'projectImagePathFilter')),
            new \Twig_SimpleFilter('baseUrl', array())
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
        return preg_replace('/[ ](?=[^>]*(?:<|$))/', '&nbsp', $sString);
    }

    public function numFilter($sString)
    {
        return $sString;
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
}
