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
        $sUrl        = '';
        $sSvgHeaders = '';
        $aUses       = array();
        $aUsesIds    = array();

        // Supported sizing sizes, using preserveAspectRatio
        $aSupportedSizes = array(
            'none'    => '',
            'stretch' => 'none',
            'cover'   => 'xMidYMid slice',
            'contain' => 'xMidYMid meet'
        );

        // Fallback to 'contain' aspect ratio if invalid option given
        if (false === empty($sSizing) && false === in_array($sSizing, $aSupportedSizes)) {
            $sSizing = 'contain';
        }

        if (false === is_array($sId)) {
            $aIds = array(str_split($sId));
        } else {
            $aIds = explode($sId, ';');
        }

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
        return $sURL;
    }


    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('route', array($this, 'routeFunction')),
            new \Twig_SimpleFunction('svgImage', array($this, 'svgImageFunction')),
            new \Twig_SimpleFunction('__', array($this, 'temporaryTranslateFunction')),
            new \Twig_SimpleFunction('siteurlmedia', array($this, 'siteurlmediaFunction'))
        );

    }



}