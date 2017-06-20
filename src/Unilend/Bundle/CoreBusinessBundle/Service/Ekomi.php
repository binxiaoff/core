<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;
use Doctrine\ORM\EntityManager;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectsStatus;

class Ekomi
{
    /** @var EntityManager */
    private $entityManager;

    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $url;

    /** @var int */
    private $shopId;

    /** @var string */
    private $password;

    /**
     * @param EntityManager   $entityManager
     * @param LoggerInterface $logger
     * @param string          $url
     * @param int             $shopId
     * @param string          $password
     */
    public function __construct(EntityManager $entityManager, LoggerInterface $logger, $url, $shopId, $password)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
        $this->url           = $url;
        $this->shopId        = $shopId;
        $this->password      = $password;
    }

    /**
     * @param Projects $project
     *
     * @return bool
     */
    public function sendProjectEmail(Projects $project)
    {
        if (empty($this->shopId) || empty($this->password)) {
            return false;
        }

        $company = $project->getIdCompany();
        if (null === $company) {
            $this->logProjectAlert($project, 'Unable to find project\'s company');
            return false;
        }

        $client = $this->entityManager->getRepository('UnilendCoreBusinessBundle:Clients')->find($company->getIdClientOwner());
        if (null === $client) {
            $this->logProjectAlert($project, 'Unable to find company owner');
            return false;
        }

        $projectStatus        = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatus')->findOneBy(['status' => ProjectsStatus::FUNDE]);
        $projectStatusHistory = $this->entityManager->getRepository('UnilendCoreBusinessBundle:ProjectsStatusHistory')->findOneBy([
            'idProjectStatus' => $projectStatus->getIdProjectStatus(),
            'idProject'       => $project->getIdProject()
        ]);

        if (null === $projectStatusHistory) {
            $this->logProjectAlert($project, 'Unable to find project funding date');
            return false;
        }

        $curl = curl_init($this->url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => http_build_query([
                'recipient_type'   => 'email',
                'shop_id'          => $this->shopId,
                'password'         => $this->password,
                'first_name'       => $client->getPrenom(),
                'last_name'        => $client->getNom(),
                'email'            => $client->getEmail(),
                'transaction_id'   => $project->getIdProject(),
                'telephone'        => $client->getTelephone(),
                'has_products'     => 0,
                'transaction_time' => $projectStatusHistory->getAdded()->format('d-m-Y H:i:s'),
                'days_of_deletion' => 60
            ])
        ]);

        $response = curl_exec($curl);
        $error    = curl_error($curl);

        curl_close($curl);

        if ($error) {
            $this->logProjectAlert($project, 'Ekomi CURL error: ' . $error);
            return false;
        }

        if (false === ($jsonResponse = json_decode($response)) || empty($jsonResponse->status)) {
            $this->logProjectAlert($project, 'Unable to decode Ekomi JSON response: ' . $response);
            return false;
        }

        if ('error' === $jsonResponse->status) {
            $this->logProjectAlert($project, 'Ekomi error: ' . $jsonResponse->message);
            return false;
        }

        return true;
    }

    /**
     * @param Projects $project
     * @param string   $message
     */
    private function logProjectAlert(Projects $project, $message)
    {
        $this->logger->error($message, ['class' => __CLASS__, 'function' => __FUNCTION__, 'projectId' => $project->getIdProject()]);
    }
}
