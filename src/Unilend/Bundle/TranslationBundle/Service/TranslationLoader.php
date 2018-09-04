<?php

namespace Unilend\Bundle\TranslationBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\Exception\{InvalidResourceException, NotFoundResourceException};
use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;
use Unilend\Bundle\CoreBusinessBundle\Entity\Translations;

class TranslationLoader implements LoaderInterface
{
    const SECTION_SEPARATOR = '_';

    private $translationRepository;
    private $defaultLocale;

    /**
     * @param EntityManager $entityManager
     * @param string        $defaultLocale
     */
    public function __construct(EntityManager $entityManager, string $defaultLocale)
    {
        $this->translationRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Translations');
        $this->defaultLocale         = $defaultLocale;
    }

    /**
     * Loads a locale.
     *
     * @param mixed  $resource A resource
     * @param string $locale   A locale
     * @param string $domain   The domain
     *
     * @return MessageCatalogue A MessageCatalogue instance
     *
     * @throws NotFoundResourceException when the resource cannot be found
     * @throws InvalidResourceException  when the resource cannot be loaded
     */
    public function load($resource, $locale, $domain = 'messages')
    {
        $catalogue = new MessageCatalogue($this->defaultLocale);

        /** @var Translations $translation */
        foreach ($this->translationRepository->findBy(['locale' => $this->defaultLocale]) as $translation) {
            $catalogue->set($translation->getSection() . self::SECTION_SEPARATOR . $translation->getName(), $translation->getTranslation(), $domain);
        }

        return $catalogue;
    }
}
