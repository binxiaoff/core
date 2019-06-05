<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as InvalidCacheArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Tree};
use Unilend\Service\Simulator\EntityManager as EntityManagerSimulator;

class CmsController extends AbstractController
{
    public const CMS_TEMPLATE_BIG_HEADER = 1;
    public const CMS_TEMPLATE_NAV        = 2;
    public const CMS_TEMPLATE_TOS        = 5;

    public const SLUG_ELEMENT_NAV_IMAGE = 'image-header';

    /**
     * @Route("/", name="home")
     *
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function home(?UserInterface $client): Response
    {
        if ($client instanceof Clients) {
            return $this->redirectToRoute('wallet');
        }

        return $this->redirectToRoute('login');
    }

    /**
     * @param Request                $request
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param CacheItemPoolInterface $cachePool
     *
     * @throws InvalidCacheArgumentException
     *
     * @return Response
     */
    public function cms(Request $request, EntityManagerSimulator $entityManagerSimulator, CacheItemPoolInterface $cachePool): Response
    {
        $slug = mb_substr($request->attributes->get('routeDocument')->getPath(), 1);

        /** @var \tree $tree */
        $tree = $entityManagerSimulator->getRepository('tree');

        if (false === $tree->get(['slug' => $slug, 'status' => Tree::STATUS_ONLINE])) {
            throw new NotFoundHttpException(sprintf('Page with slug %s could not be found', $slug));
        }

        $cachedItem = $cachePool->getItem('Home_Tree_Childs_Elements_' . $tree->id_tree);

        if (false === $cachedItem->isHit()) {
            $content    = [];
            $complement = [];

            /** @var \tree_elements $treeElements */
            $treeElements = $entityManagerSimulator->getRepository('tree_elements');

            foreach ($treeElements->selectWithDefinition('id_tree = ' . $tree->id_tree, 'ordre ASC') as $element) {
                $content[$element['slug']]    = $element['value'];
                $complement[$element['slug']] = $element['complement'];
            }

            $finalElements = [
                'content'    => $content,
                'complement' => $complement,
            ];

            $cachedItem->set($finalElements)->expiresAfter(3600);
            $cachePool->save($cachedItem);
        } else {
            $finalElements = $cachedItem->get();
        }

        switch ($tree->id_template) {
            case self::CMS_TEMPLATE_BIG_HEADER:
                return $this->renderCmsBigHeader($finalElements['content']);
            case self::CMS_TEMPLATE_NAV:
                return $this->renderCmsNav($tree, $finalElements['content'], $entityManagerSimulator);
            case self::CMS_TEMPLATE_TOS:
                return $this->redirectToRoute('lenders_terms_of_sales');
            default:
                return new RedirectResponse('/');
        }
    }

    /**
     * @param array $content
     *
     * @return Response
     */
    private function renderCmsBigHeader(array $content): Response
    {
        $cms = [
            'title'         => $content['titre'],
            'header_image'  => $content['image-header'],
            'left_content'  => $content['bloc-gauche'],
            'right_content' => $content['bloc-droite'],
        ];

        return $this->render('cms_templates/template_big_header.html.twig', ['cms' => $cms]);
    }

    /**
     * @param \tree                  $currentPage
     * @param array                  $content
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param string|null            $pageId
     *
     * @return Response
     */
    private function renderCmsNav(\tree $currentPage, array $content, EntityManagerSimulator $entityManagerSimulator, ?string $pageId = null): Response
    {
        /** @var \tree $pages */
        $pages = $entityManagerSimulator->getRepository('tree');

        $selected   = false;
        $navigation = [];
        $nextPage   = [];
        foreach ($pages->select('status = 1 AND prive = 0 AND id_parent = ' . $currentPage->id_parent, 'ordre ASC') as $page) {
            // If previous page was current page, it means we are now processing "next" page
            if ($selected) {
                /** @var \tree_elements $treeElements */
                $treeElements = $entityManagerSimulator->getRepository('tree_elements');

                foreach ($treeElements->selectWithDefinition('id_tree = ' . $page['id_tree'], 'ordre ASC') as $element) {
                    if (self::SLUG_ELEMENT_NAV_IMAGE === $element['slug']) {
                        $nextPage = [
                            'label'        => $page['menu_title'],
                            'slug'         => $page['slug'],
                            'header_image' => $element['value'],
                        ];

                        break;
                    }
                }
            }

            $selected = (int) $page['id_tree'] === (int) $currentPage->id_tree;

            $navigation[$page['id_tree']] = [
                'label'                => $page['menu_title'],
                'slug'                 => $page['slug'],
                'selected'             => $selected,
                'highlighted_lender'   => false,
                'highlighted_borrower' => false,
            ];
        }

        $cms = [
            'header_image' => $content['image-header'],
            'content'      => $content['contenu'],
        ];

        $page = [
            'id'    => $pageId,
            'title' => $currentPage->meta_title,
            'next'  => $nextPage,
        ];

        return $this->render('cms_templates/template_nav.html.twig', ['navigation' => $navigation, 'cms' => $cms, 'page' => $page]);
    }
}
