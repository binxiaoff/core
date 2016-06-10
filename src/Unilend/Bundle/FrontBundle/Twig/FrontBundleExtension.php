<?php
namespace Unilend\Bundle\FrontBundle\Twig;


use Unilend\Bundle\CoreBusinessBundle\Service\StatisticsManager;

class FrontBundleExtension extends \Twig_Extension
{

    private $sUrl;

    /** @var  StatisticsManager */
    private $statisticsManager;

    public function __construct($routerRequestContextScheme, $routerRequestContextHost, $statisticsManager)
    {
        $this->sUrl = $routerRequestContextHost . '://' . $routerRequestContextScheme;
        $this->statisticsManager = $statisticsManager;
    }

    public function getName()
    {
        return 'app_extension';
    }

    public function svgImageFunction($sId, $sTitle, $iWidth, $iHeight, $sSizing = null)
    {
        $sUrl        = $this->sUrl . '/frontbundle/media/svg/icons.svg'; //TODO change place according to asset management
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

    public function siteurlmediaFunction($sPath)
    {
        return $this->sUrl . 'frontbundle/media/' . $sPath;
    }

    public function canUseSvg()
    {
        //var useSVG = GLOBAL.Unilend.config.useSVG || false
        return true;
    }

    public function getStatistics()
    {
        return $this->statisticsManager->getAllStatistics();
    }


    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('route', array($this, 'routeFunction')),
            new \Twig_SimpleFunction('svgimage', array($this, 'svgImageFunction')),
            new \Twig_SimpleFunction('__', array($this, 'temporaryTranslateFunction')),
            new \Twig_SimpleFunction('siteurlmedia', array($this, 'siteurlmediaFunction')),
            new \Twig_SimpleFunction('caniuse_svg', array($this, 'canUseSvg')),
            new \Twig_SimpleFunction('getStatistics', array($this, 'getStatistics'))
        );
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
        return $this->sUrl . '/images/dyn/projets/72/' . $sImage;
    }

    public function addBaseUrl($sUrl)
    {
        return $this->sUrl . $sUrl;
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



}
