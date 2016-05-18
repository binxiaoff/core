<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Translation\Exception\NotFoundResourceException;
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Unilend\Service\Simulator\EntityManager;


class TranslationLoader implements LoaderInterface
{
    /**
     * @var \translations
     */
    private $translationRepository;

    public function __construct(EntityManager $entityManager, $defaultLanguage)
    {
        $this->translationRepository = $entityManager->getRepository('translations');
        $this->defaultLanguage       = $defaultLanguage;

    }

    /**
     * Loads a locale.
     *
     * @param mixed $resource A resource
     * @param string $locale A locale
     * @param string $domain The domain
     *
     * @return MessageCatalogue A MessageCatalogue instance
     *
     * @throws NotFoundResourceException when the resource cannot be found
     * @throws InvalidResourceException  when the resource cannot be loaded
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $translations = $this->translationRepository->getAllTranslationMessages($this->defaultLanguage);
        $catalogue    = new MessageCatalogue($this->defaultLanguage);

        foreach ($translations as $translation) {
            $catalogue->set($translation['section'] . '_' . $translation['name'], $translation['translation'], $domain);
        }

        return $catalogue;
    }
}