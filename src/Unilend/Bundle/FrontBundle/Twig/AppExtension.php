<?php
namespace Unilend\Bundle\FrontBundle\Twig;


class AppExtension extends \Twig_Extension
{
    public function getName()
    {
        return 'app_extension';
    }

    public function svgImageFunction($sId, $sTitle, $iWidth, $iHeight, $sSizing = null)
    {
        $sUrl        = 'frontbundle/media/svg/icons.svg'; //TODO change place according to asset management
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

    public function routeFunction($sRoute)
    {
        return $sRoute;
    }

    public function temporaryTranslateFunction($sTranslations)
    {
        return $sTranslations;
    }

    public function siteurlmediaFunction($sURL)
    {
        return 'frontbundle/media/' . $sURL;
    }


    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('route', array($this, 'routeFunction')),
            new \Twig_SimpleFunction('svgimage', array($this, 'svgImageFunction')),
            new \Twig_SimpleFunction('__', array($this, 'temporaryTranslateFunction')),
            new \Twig_SimpleFunction('siteurlmedia', array($this, 'siteurlmediaFunction'))
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

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('nbsp', array($this, 'nbspFilter')),
            new \Twig_SimpleFilter('__num', array($this, 'numFilter'))

        );
    }



}