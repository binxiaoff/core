<?php

namespace Unilend\Bundle\FrontBundle\Service;

use Symfony\Component\HttpFoundation\RequestStack;

class SourceManager
{
    const SOURCE1    = 'user_source_utm_source_1';
    const SOURCE2    = 'user_source_utm_source_2';
    const SOURCE3    = 'user_source_utm_source_campaign';
    const ENTRY_SLUG = 'user_source_entry_slug';

    const HP_LENDER_SOURCE_NAME      = 'HomePreteur';
    const HP_LENDER_SOURCE_PARAMETER = 'hl';
    const HP_SOURCE_NAME             = 'HomePage';
    const HP_SOURCE_PARAMETER        = 'hm';

    /** @var RequestStack */
    private $requestStack;
    /** @var array */
    private $sources;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        $this->sources = [
            self::SOURCE1    => 'utm_source',
            self::SOURCE2    => 'utm_source2',
            self::SOURCE3    => 'utm_campaign',
            self::ENTRY_SLUG => 'slug_origine'
        ];
    }

    public function handle(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        foreach ($this->sources as $sessionKey => $sourceKey) {
            if (self::ENTRY_SLUG === $sessionKey) {
                if (false === $session->has(self::ENTRY_SLUG)) {
                    $slug = filter_var($request->getPathInfo(), FILTER_SANITIZE_STRING);
                    $session->set($sessionKey, $slug);
                }
            } else {
                $source = filter_var($request->get($sourceKey), FILTER_SANITIZE_STRING);
                if (false === empty($source)) {
                    $session->set($sessionKey, $source);
                }
            }
        }

        if (false === $session->has(self::SOURCE1)) {
            $this->setSource(self::SOURCE1, 'Directe');
        }

        if (false === $session->has(self::SOURCE2) && 'Directe' == $this->getSource(self::SOURCE1)) {
            if (false === empty($request->query->get('source'))) {
                switch ($request->query->get('source')) {
                    case self::HP_LENDER_SOURCE_PARAMETER:
                        $this->setSource(self::SOURCE2, self::HP_LENDER_SOURCE_NAME);
                        break;
                    case self::HP_SOURCE_PARAMETER:
                        $this->setSource(self::SOURCE2, self::HP_SOURCE_NAME);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    public function setSource(string $key, string $value): void
    {
        if (in_array($key, array_keys($this->sources))) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();
            $session->set($key, $value);
        }
    }

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function getSource(string $key)
    {
        if (in_array($key, array_keys($this->sources))) {
            if ($request = $this->requestStack->getCurrentRequest()) {
                if ($session = $request->getSession()) {
                    return $session->get($key);
                }
            }
        }

        return null;
    }
}
