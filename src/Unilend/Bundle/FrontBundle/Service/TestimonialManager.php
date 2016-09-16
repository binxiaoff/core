<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Cache\Adapter\Memcache\MemcacheCachePool;

class TestimonialManager
{
    /** @var  EntityManager */
    private $entityManager;

    /** @var MemcacheCachePool */
    private $cachePool;

    public function __construct(EntityManager $entityManager, MemcacheCachePool $cachePool)
    {
        $this->entityManager      = $entityManager;
        $this->cachePool          = $cachePool;
    }

    public function getFeaturedTestimonialBorrower()
    {
        $cachedItem = $this->cachePool->getItem('featuredTestimonialBorrower');

//        if (false === $cachedItem->isHit()) {
            /** @var \testimonial $testimonial */
            $testimonial = $this->entityManager->getRepository('testimonial');
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('temoignage home emprunteur', 'type');
            $featuredTestimonial = $testimonial->select('slider_id = "' . $settings->value . '"')[0];
            $cachedItem->set($featuredTestimonial)->expiresAfter(14400);
            $this->cachePool->save($cachedItem);

            return $featuredTestimonial;
//        } else {
//            return $cachedItem->get();
//        }
    }

    public function getFeaturedTestimonialLender()
    {
        $cachedItem = $this->cachePool->getItem('featuredTestimonialLender');

//        if (false === $cachedItem->isHit()) {
            /** @var \testimonial $testimonial */
            $testimonial = $this->entityManager->getRepository('testimonial');
            /** @var \settings $settings */
            $settings = $this->entityManager->getRepository('settings');
            $settings->get('temoignage home preteur', 'type');
            $featuredTestimonial = $testimonial->select('slider_id = "' . $settings->value . '"')[0];

            $cachedItem->set($featuredTestimonial)->expiresAfter(14400);
            $this->cachePool->save($cachedItem);

            return $featuredTestimonial;
//        } else {
//            return $cachedItem->get();
//        }
    }

    /**
     * @param bool $excludeFeatured
     * @return array|mixed|null|void
     */
    public function getBorrowerBattenbergTestimonials($excludeFeatured)
    {
        /** @var \testimonial $testimonial */
        $testimonial = $this->entityManager->getRepository('testimonial');
        /** @var \settings $settings */
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('temoignage home emprunteur', 'type');

        if ($excludeFeatured) {
            $cachedItem = $this->cachePool->getItem('BorrowerBattenbergExcludingFeatured');
//            if (false === $cachedItem->isHit()) {
                $allTestimonials = $testimonial->select('type = "borrower" AND status = ' . \testimonial::TESTIMONIAL_ONLINE . ' AND slider_id != "' . $settings->value . '"');
                $this->addProjectInfoToBorrowerTestimonial($allTestimonials);
                $cachedItem->set($allTestimonials)->expiresAfter(14400);
                $this->cachePool->save($cachedItem);

                return $allTestimonials;
//            } else {
//                return $cachedItem->get();
//            }

        } else {
            $cachedItem = $this->cachePool->getItem('BorrowerBattenbergAll');
//            if (false === $cachedItem->isHit()) {
                $allTestimonials = $testimonial->select('type = "borrower" AND status = ' . \testimonial::TESTIMONIAL_ONLINE);
                $this->addProjectInfoToBorrowerTestimonial($allTestimonials);
                $cachedItem->set($allTestimonials)->expiresAfter(14400);
                $this->cachePool->save($cachedItem);

                return $allTestimonials;
//            } else {
//                return $cachedItem->get();
//            }
        }
    }

    public function getAllBattenbergTestimonials()
    {
        $cachedItem = $this->cachePool->getItem('BattenbergAll');
//        if (false === $cachedItem->isHit()) {
            /** @var \testimonial $testimonial */
            $testimonial = $this->entityManager->getRepository('testimonial');
            $allTestimonials = $testimonial->select('status = ' . \testimonial::TESTIMONIAL_ONLINE);
            $this->addProjectInfoToBorrowerTestimonial($allTestimonials);
            $cachedItem->set($allTestimonials)->expiresAfter(14400);
            $this->cachePool->save($cachedItem);

            return $allTestimonials;
//        } else {
//            return $cachedItem->get();
//        }
    }


    /**
     * @param array $allTestimonials
     */
    private function addProjectInfoToBorrowerTestimonial(&$allTestimonials)
    {
        foreach ($allTestimonials as $key => $entry) {
            if ($entry['type'] == 'borrower') {
                /** @var \projects $projects */
                $projects = $this->entityManager->getRepository('projects');
                /** @var \companies $companies */
                $companies = $this->entityManager->getRepository('companies');
                $companies->get($entry['id_client'], 'id_client_owner');
                $allTestimonials[$key]['project'] = $projects->select('id_company = ' . $companies->id_company, 'date_publication_full DESC', null, 1)[0];
            }
        }
    }

    public function getSliderInformation()
    {
        $allTestimonials    = $this->getAllBattenbergTestimonials();
        $testimonialsByType = [];

        foreach ($allTestimonials as $testimonial) {
            $testimonialsByType[$testimonial['slider_id']] = $testimonial;
        }

        $sliderTestimonials = [
            'P1_E1' => ['image'    => '1682x650_0000_P1-E1.jpg',
                        'lender'   => $testimonialsByType['P1'],
                        'borrower' => $testimonialsByType['E1']
            ],
            'P1_E2' => ['image'    => '1682x650_0001_P1-E2.jpg',
                        'lender'   => $testimonialsByType['P1'],
                        'borrower' => $testimonialsByType['E2']
            ],
            'P2_E2' => ['image'    => '1682x650_0004_P2-E2.jpg',
                        'lender'   => $testimonialsByType['P2'],
                        'borrower' => $testimonialsByType['E2']
            ],
            'P2_E3' => ['image'    => '1682x650_0005_P2-E3.jpg',
                        'lender'   => $testimonialsByType['P2'],
                        'borrower' => $testimonialsByType['E3']
            ],
            'P3_E3' => ['image'    => '1682x650_0008_P3-E3.jpg',
                        'lender'   => $testimonialsByType['P3'],
                        'borrower' => $testimonialsByType['E3']
            ],
            'P3_E1' => ['image'    => '1682x650_0006_P3-E1.jpg',
                        'lender'   => $testimonialsByType['P3'],
                        'borrower' => $testimonialsByType['E1']
            ],
            'P2_E1' => ['image'    => '1682x650_0003_P2-E1.jpg',
                        'lender'   => $testimonialsByType['P2'],
                        'borrower' => $testimonialsByType['E1']
            ],
            'P1_E3' => ['image'    => '1682x650_0002_P1-E3.jpg',
                        'lender'   => $testimonialsByType['P1'],
                        'borrower' => $testimonialsByType['E3']
            ],
            'P3_E2' => ['image'    => '1682x650_0007_P3-E2.jpg',
                        'lender'   => $testimonialsByType['P3'],
                        'borrower' => $testimonialsByType['E2']
            ]
        ];

        return $sliderTestimonials;
    }
}
