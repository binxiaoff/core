<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class SeoManager
{
    private $translator;
    private $seoPage;

    public function __construct(SeoPage $seoPage, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->seoPage    = $seoPage;
    }

    /**
     * @param Request $request
     */
    public function setSeoData(Request $request)
    {
        $route           = $request->attributes->get('_route');
        $translationName = str_replace('_', '-', $route);

        if (false === empty($translationName)) {
            $pageTitle           = $this->translator->trans('seo_' . $translationName . '-title');
            $pageMetaDescription = $this->translator->trans('seo_' . $translationName . '-description');
            $pageMetaKeywords    = $this->translator->trans('seo_' . $translationName . '-keywords');

            if ($pageTitle !== 'seo_' . $translationName . '-title') {
                $this->seoPage->setTitle($pageTitle);
            }
            if ($pageMetaDescription !== 'seo_' . $translationName . '-description') {
                $this->seoPage->addMeta('name', 'description', $pageMetaDescription);
            }
            if ($pageMetaKeywords !== 'seo_' . $translationName . '-keywords') {
                $this->seoPage->addMeta('name', 'keywords', $pageMetaKeywords);
            }
        }
    }
}
