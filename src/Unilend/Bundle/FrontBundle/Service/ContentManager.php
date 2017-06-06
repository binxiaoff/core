<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Psr\Cache\CacheItemPoolInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\librairies\CacheKeys;

class ContentManager
{
    /** @var  EntityManager */
    private $entityManager;
    /** @var  CacheItemPoolInterface */
    private $cachePool;

    public function __construct(EntityManager $entityManager, CacheItemPoolInterface $cachePool)
    {
        $this->entityManager = $entityManager;
        $this->cachePool = $cachePool;
    }

    public function getFooterMenu()
    {
        $cachedItem = $this->cachePool->getItem(CacheKeys::FOOTER_MENU);

        if (false === $cachedItem->isHit()) {
            /** @var \menus $menus */
            $menus = $this->entityManager->getRepository('menus');
            /** @var \tree_menu $subMenus */
            $subMenus = $this->entityManager->getRepository('tree_menu');
            /** @var \tree $page */
            $page = $this->entityManager->getRepository('tree');

            $footerMenu = [];
            foreach ($menus->select('status = 1', 'id_menu ASC') as $menu) {
                $children = [];
                foreach ($subMenus->select('status = 1 AND id_menu = ' . $menu['id_menu'], 'ordre ASC') as $subMenu) {
                    $children[] = [
                        'title'  => $subMenu['nom'],
                        'target' => $subMenu['target'],
                        'url'    => ($subMenu['complement'] === 'L' && $page->get(['id_tree' => $subMenu['value']])) ? '/' . $page->slug : $subMenu['value']
                    ];
                }

                $footerMenu[] = [
                    'title'    => $menu['nom'],
                    'children' => $children
                ];
            }

            $cachedItem->set($footerMenu)->expiresAfter(CacheKeys::LONG_TIME);
            $this->cachePool->save($cachedItem);
        } else {
            $footerMenu = $cachedItem->get();
        }

        return $footerMenu;
    }
}
