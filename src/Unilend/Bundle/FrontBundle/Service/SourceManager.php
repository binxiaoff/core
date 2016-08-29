<?php
namespace Unilend\Bundle\FrontBundle\Service;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Xynnn\GoogleTagManagerBundle\Service\GoogleTagManager;

class SourceManager
{
    const SOURCE1    = 'user_source_utm_source_1';
    const SOURCE2    = 'user_source_utm_source_2';
    const SOURCE3    = 'user_source_utm_source_campaign';
    const ENTRY_SLUG = 'user_source_entry_slug';

    /** @var GoogleTagManager */
    private $googleTagManager;
    /** @var RequestStack */
    private $requestStack;

    public function __construct(GoogleTagManager $googleTagManager, RequestStack $requestStack)
    {
        $this->googleTagManager = $googleTagManager;
        $this->requestStack     = $requestStack;

        $this->sources = [
            self::SOURCE1    => 'utm_source',
            self::SOURCE2    => 'utm_source2',
            self::SOURCE3    => 'utm_campaign',
            self::ENTRY_SLUG => 'slug_origine'
        ];
    }

    public function handleSource()
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request->getSession();

        foreach ($this->sources as $sessionKey => $sourceKey) {
            if (self::ENTRY_SLUG === $sessionKey) {
                if (false === $session->has(self::ENTRY_SLUG)) {
                    $slug = $request->getRequestUri();
                    $session->set($sessionKey, $slug);
                    $this->googleTagManager->addData($sourceKey, $slug);
                }
            } else {
                $source = $request->get($sourceKey);
                if (false === empty($source)) {
                    $session->set($sessionKey, $source);
                    $this->googleTagManager->addData($sourceKey, $source);
                }
            }
        }

        if (false === $session->has(self::SOURCE1)) {
            $this->setSource(self::SOURCE1, 'Directe');
        }
    }

    public function setSource($key, $value)
    {
        if (in_array($key, array_keys($this->sources))) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();
            $session->set($key, $value);
        }
    }

    public function getSource($key)
    {
        if (in_array($key, array_keys($this->sources))) {
            $request = $this->requestStack->getCurrentRequest();
            $session = $request->getSession();

            return $session->get($key);
        }
    }
}
