<?php

declare(strict_types=1);

namespace Unilend\SwiftMailer;

use Doctrine\ORM\NonUniqueResultException;
use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use Unilend\Entity\MailTemplate;
use Unilend\Repository\MailTemplateRepository;

class MailTemplateLoader implements LoaderInterface
{
    /** @var string */
    private $locale;

    /**
     * @var MailTemplateRepository
     */
    private $mailTemplateRepository;

    /**
     * MailTemplateLoader constructor.
     *
     * @param MailTemplateRepository $mailTemplateRepository
     * @param string                 $locale
     */
    public function __construct(
        MailTemplateRepository $mailTemplateRepository,
        string $locale = 'fr_FR'
    ) {
        $this->locale                 = $locale;
        $this->mailTemplateRepository = $mailTemplateRepository;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @throws LoaderError              When $name is not found
     * @throws NonUniqueResultException
     *
     * @return Source
     */
    public function getSourceContext($name): Source
    {
        /** @var MailTemplate $mailTemplate */
        $mailTemplate = $this->getMailTemplate($name);

        if (!$mailTemplate) {
            $this->throwNotFoundError($name);
        }

        $sourceCode = $mailTemplate->getLayout()->getContent();

        $parts = [
            'header' => ($mailHeader = $mailTemplate->getHeader()) ? $mailHeader->getContent() : null,
            'body'   => $mailTemplate->getContent(),
            'footer' => ($mailFooter = $mailTemplate->getFooter()) ? $mailFooter->getContent() : null,
        ];

        $parts = array_filter($parts);

        foreach ($parts as $part => $content) {
            $content    = "{% block {$part} -%} " . $content . " {%- endblock {$part} %}";
            $sourceCode = preg_replace("/{%\\s*block\\s+{$part}\\s*%}.*{%\\s*endblock(?:\\s*|\\s+{$part}\\s*)%}/misU", $content, $sourceCode);
        }

        return new Source($sourceCode, $mailTemplate->getType());
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @throws LoaderError
     * @throws NonUniqueResultException
     *
     * @return string The cache key
     */
    public function getCacheKey($name): string
    {
        $mailTemplate = $this->getMailTemplate($name);

        if (!$mailTemplate) {
            $this->throwNotFoundError($name);
        }

        return $this->getMailTemplate($name)->getType();
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @throws LoaderError              When $name is not found
     * @throws NonUniqueResultException
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isFresh($name, $time): bool
    {
        $mailTemplate = $this->getMailTemplate($name);

        if (!$mailTemplate) {
            $this->throwNotFoundError($name);
        }

        $date = $mailTemplate->getUpdated() ?? $mailTemplate->getAdded();

        return $date->getTimestamp() <= $time;
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @throws NonUniqueResultException
     *
     * @return bool
     */
    public function exists($name): bool
    {
        return null !== $this->getMailTemplate($name);
    }

    /**
     * @param MailTemplate|string $template
     *
     * @throws NonUniqueResultException
     *
     * @return MailTemplate
     */
    private function getMailTemplate($template): MailTemplate
    {
        return $template instanceof MailTemplate ? $template :
            $this->mailTemplateRepository->findMostRecentByTypeAndLocale($template, $this->locale);
    }

    /**
     * @param string $name
     *
     * @throws LoaderError
     */
    private function throwNotFoundError(string $name): void
    {
        throw new LoaderError(sprintf("Mail template with type %s doesn't exist", $name));
    }
}
