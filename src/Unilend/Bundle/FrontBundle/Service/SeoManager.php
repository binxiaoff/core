<?php
namespace Unilend\Bundle\FrontBundle\Service;

use Sonata\SeoBundle\Seo\SeoPage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Entity\Tree;

class SeoManager
{
    /** @var TranslatorInterface */
    private $translator;
    /** @var SeoPage  */
    private $seoPage;

    public function __construct(SeoPage $seoPage, TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->seoPage    = $seoPage;
    }

    /**
     * @param Request $request
     */
    public function setSeoData(Request $request): void
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

    /**
     * @param \tree|Tree $tree
     */
    public function setCmsSeoData($tree): void
    {
        $metaTitle       = $tree instanceof Tree ? $tree->getMetaTitle() : ($tree instanceof \tree ? $tree->meta_title : '');
        $metaDescription = $tree instanceof Tree ? $tree->getMetaDescription() : ($tree instanceof \tree ? $tree->meta_description : '');
        $metaKeyWords    = $tree instanceof Tree ? $tree->getMetaKeyWords() : ($tree instanceof \tree ? $tree->meta_keywords : '');
        $indexation      = $tree instanceof Tree ? $tree->getIndexation() : ($tree instanceof \tree ? $tree->indexation : '');

        if (false === empty($metaTitle)) {
            $this->seoPage->setTitle($metaTitle);
        }

        if (false === empty($metaDescription)) {
            $this->seoPage->addMeta('name', 'description', $metaDescription);
        }

        if (false === empty($metaKeyWords)) {
            $this->seoPage->addMeta('name', 'keywords', $metaKeyWords);
        }

        if (empty($indexation)) {
            $this->seoPage->addMeta('name', 'robots', 'noindex');
        }
    }
}
