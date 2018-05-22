<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use DrewM\MailChimp\MailChimp;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class NewsletterManager
{
    const MAILCHIMP_STATUS_SUBSCRIBED   = 'subscribed';
    const MAILCHIMP_STATUS_UNSUBSCRIBED = 'unsubscribed';

    /** @var EntityManager */
    private $entityManager;
    /** @var MailChimp */
    private $mailChimp;
    /** @var string */
    private $listId;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManager   $entityManager
     * @param MailChimp       $mailChimp
     * @param string          $listId
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $entityManager, MailChimp $mailChimp, string $listId, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->mailChimp     = $mailChimp;
        $this->listId        = $listId;
        $this->logger        = $logger;
    }

    /**
     * @param Clients     $client
     * @param string|null $ipAddress
     *
     * @return bool
     */
    public function subscribeNewsletter(Clients $client, ?string $ipAddress = null): bool
    {
        return $this->updateNewsletterSubscription($client, self::MAILCHIMP_STATUS_SUBSCRIBED, $ipAddress);
    }

    /**
     * @param Clients     $client
     * @param string|null $ipAddress
     *
     * @return bool
     */
    public function unsubscribeNewsletter(Clients $client, ?string $ipAddress = null): bool
    {
        return $this->updateNewsletterSubscription($client, self::MAILCHIMP_STATUS_UNSUBSCRIBED, $ipAddress);
    }

    /**
     * @param Clients     $client
     * @param string      $status
     * @param string|null $ipAddress
     *
     * @return bool
     */
    private function updateNewsletterSubscription(Clients $client, string $status, ?string $ipAddress = null): bool
    {
        $this->mailChimp->put('lists/' . $this->listId . '/members/' . md5(strtolower($client->getEmail())), [
            'email_address'    => $client->getEmail(),
            'email_type'       => 'html',
            'status'           => $status,
            'merge_fields'     => [
                'FNAME' => $client->getPrenom(),
                'LNAME' => $client->getNom(),
            ],
            'ip_signup'        => $ipAddress,
            'timestamp_signup' => date('Y-m-d H:i:s'),
            'ip_opt'           => $ipAddress,
            'timestamp_opt'    => date('Y-m-d H:i:s'),
        ]);

        if (false !== $this->mailChimp->getLastError()) {
            $this->logger->error('Could not update lender newsletter subscription. MailChimp API error: ' . $this->mailChimp->getLastError(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            return false;
        }

        $optIn = self::MAILCHIMP_STATUS_SUBSCRIBED === $status ? Clients::NEWSLETTER_OPT_IN_ENROLLED : Clients::NEWSLETTER_OPT_IN_NOT_ENROLLED;
        $client->setOptin1($optIn);

        try {
            $this->entityManager->flush($client);
        } catch (OptimisticLockException $exception) {
            $this->logger->error('Could not update lender newsletter subscription. Error: ' . $exception->getMessage(), [
                'id_client' => $client->getIdClient(),
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);

            return false;
        }

        return true;
    }
}
