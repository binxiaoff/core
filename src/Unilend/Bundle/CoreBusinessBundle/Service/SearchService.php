<?php
namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SearchService
{
    /** @var EntityManager */
    private $entityManager;

    /** @var string */
    private $deskUser;

    /** @var string */
    private $deskPassword;

    /**
     * @param EntityManager $entityManager
     * @param string        $deskUser
     * @param string        $deskPassword
     */
    public function __construct(EntityManager $entityManager, $deskUser, $deskPassword)
    {
        $this->entityManager = $entityManager;
        $this->deskUser      = $deskUser;
        $this->deskPassword  = $deskPassword;
    }

    /**
     * @param string $query
     * @param bool   $includeProjects
     * @return array
     */
    public function search($query, $includeProjects = false)
    {
        /** @var \tree $tree */
        $tree   = $this->entityManager->getRepository('tree');
        $result = $tree->search($query, $includeProjects);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://unilend.desk.com/api/v2/articles/search?text=' . urlencode($query));
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->deskUser . ':' . $this->deskPassword);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);

        if ($response) {
            $response = json_decode($response, true);

            if (isset($response['_embedded']['entries']) && false === empty($response['_embedded']['entries'])) {
                $deskResult = [];

                foreach ($response['_embedded']['entries'] as $entry) {
                    $deskResult[] = [
                        'title' => $entry['subject'],
                        'url'   => $entry['public_url']
                    ];
                }

                $result = array_merge(array('desk' => $deskResult), $result);
                usort($result['desk'], function($firstElement, $secondElement) {
                    return strcmp($firstElement['title'], $secondElement['title']);
                });
            }
        }

        return $result;
    }
}
