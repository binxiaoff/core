<?php

namespace Unilend\Bundle\TranslationBundle;

use Symfony\Bundle\FrameworkBundle\Translation\TranslationLoader;
use Unilend\Service\Simulator\EntityManager;


class TranslationManager
{
    /** @var EntityManager  */
    private $entityManager;

    /** @var TranslationLoader  */
    private $translationLoader;

    public function __construct(EntityManager $entityManager, TranslationLoader $translationLoader, $defaultLanguage)
    {
        $this->entityManager     = $entityManager;
        $this->translationLoader = $translationLoader;
        $this->defaultLanguage   = $defaultLanguage;
    }

    private function clearLanguageCache()
    {
        $cacheDir = __DIR__ . '/../../../../var/cache';
        $finder = new \Symfony\Component\Finder\Finder();

        $finder->in(array($cacheDir . '/dev/translations', $cacheDir . '/prod/translations'))->files();

        foreach($finder as $file) {
            unlink($file->getRealpath());
        }
    }



}