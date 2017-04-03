<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

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
     * @param \projects $project
     * @return bool
     */
    public function sendProjectEmail(\projects $project)
    {
        if (empty($this->shopId) || empty($this->password)) {
            return false;
        }

        /** @var \companies $company */
        $company = $this->entityManager->getRepository('companies');
        $company->get($project->id_company);

        /** @var \clients $client */
        $client = $this->entityManager->getRepository('clients');
        $client->get($company->id_client_owner);

        /** @var \projects_status $projectStatus */
        $projectStatus = $this->entityManager->getRepository('projects_status');
        $projectStatus->get(\projects_status::FUNDE, 'status');

        /** @var \projects_status_history $projectStatusHistory */
        $projectStatusHistory = $this->entityManager->getRepository('projects_status_history');

        if (false === $projectStatusHistory->get($project->id_project, 'id_project_status = ' . $projectStatus->id_project_status . ' AND id_project')) {
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
                'first_name'       => $client->prenom,
                'last_name'        => $client->nom,
                'email'            => $client->email,
                'transaction_id'   => $project->id_project,
                'telephone'        => $client->telephone,
                'has_products'     => 0,
                'transaction_time' => \DateTime::createFromFormat('Y-m-d H:i:s', $projectStatusHistory->added)->format('d-m-Y H:i:s'),
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
     * @param \projects $project
     */
    private function logProjectAlert(\projects $project, $message)
    {
        $this->logger->alert($message, ['class' => __CLASS__, 'function' => __FUNCTION__, 'projectId' => $project->id_project]);
    }
}
