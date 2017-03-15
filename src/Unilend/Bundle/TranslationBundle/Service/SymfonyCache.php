<?php
namespace Unilend\Bundle\TranslationBundle\Service;

class SymfonyCache extends \Sonata\CacheBundle\Adapter\SymfonyCache
{
    public function flush(array $keys = ['all'])
    {
        $result = true;

        foreach ($this->servers as $server) {
            foreach ($keys as $type) {
                if (false === filter_var($server['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                    && false === filter_var($server['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                ) {
                    throw new \InvalidArgumentException(sprintf('"%s" is not a valid ip address', $server['ip']));
                }

                $ch = curl_init($this->router->getContext()->getScheme() . '://' . $server['domain'] . $this->getUrl($type));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_AUTOREFERER, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
                curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Host: ' . $server['domain']]);
                curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                if ($server['basic']) {
                    curl_setopt($ch, CURLOPT_USERPWD, $server['basic']);
                }

                if ($content = curl_exec($ch)) {
                    $result = $content == 'ok';
                } else {
                    return false;
                }
                curl_close($ch);
            }
        }

        return $result;
    }
}
