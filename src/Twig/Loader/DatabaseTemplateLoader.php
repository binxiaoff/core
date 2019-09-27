<?php

declare(strict_types=1);

namespace Unilend\Twig\Loader;

use Twig\Error\LoaderError;
use Twig\Loader\LoaderInterface;
use Twig\Source;
use Unilend\Entity\Interfaces\TwigTemplateInterface;
use Unilend\Repository\Interfaces\TwigTemplateRepositoryInterface;

class DatabaseTemplateLoader implements LoaderInterface
{
    /** @var string */
    private $locale;

    /** @var TwigTemplateRepositoryInterface */
    private $repository;

    /**
     * @param TwigTemplateRepositoryInterface $repository
     * @param string                          $locale
     */
    public function __construct(
        TwigTemplateRepositoryInterface $repository,
        string $locale = 'fr_FR'
    ) {
        $this->locale     = $locale;
        $this->repository = $repository;
    }

    /**
     * Returns the source context for a given template logical name.
     *
     * @param string $name The template logical name
     *
     * @throws LoaderError When $name is not found
     *
     * @return Source
     */
    public function getSourceContext($name): Source
    {
        $template = $this->getTemplate($name);

        if (!$template) {
            $this->throwNotFoundError($name);
        }

        return $template->getSource();
    }

    /**
     * Gets the cache key to use for the cache for a given template name.
     *
     * @param string $name The name of the template to load
     *
     * @throws LoaderError
     *
     * @return string The cache key
     */
    public function getCacheKey($name): string
    {
        $template = $this->getTemplate($name);

        if (!$template) {
            $this->throwNotFoundError($name);
        }

        return $this->getTemplate($name)->getName();
    }

    /**
     * Returns true if the template is still fresh.
     *
     * @param string $name The template name
     * @param int    $time Timestamp of the last modification time of the
     *                     cached template
     *
     * @throws LoaderError
     *
     * @return bool true if the template is fresh, false otherwise
     */
    public function isFresh($name, $time): bool
    {
        $template = $this->getTemplate($name);

        if (!$template) {
            $this->throwNotFoundError($name);
        }

        $date = $template->getUpdated() ?? $template->getAdded();

        return $date->getTimestamp() <= $time;
    }

    /**
     * Check if we have the source code of a template, given its name.
     *
     * @param string $name The name of the template to check if we can load
     *
     * @return bool
     */
    public function exists($name): bool
    {
        return null !== $this->getTemplate($name);
    }

    /**
     * @param TwigTemplateInterface|string $template
     *
     * @return TwigTemplateInterface|null
     */
    private function getTemplate($template): ?TwigTemplateInterface
    {
        return $template instanceof TwigTemplateInterface ? $template :
            $this->repository->findOneBy(['name' => $template, 'locale' => $this->locale]);
    }

    /**
     * @param string $name
     *
     * @throws LoaderError
     */
    private function throwNotFoundError(string $name): void
    {
        throw new LoaderError(sprintf('Template with name %s not found', $name));
    }
}
