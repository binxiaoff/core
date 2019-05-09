<?php

namespace Unilend\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use Unilend\Entity\Clients;

/**
 * @link https://developer.mailchimp.com/documentation/mailchimp/guides/manage-subscribers-with-the-mailchimp-api/
 */
class NewsletterManager
{
    const MAILCHIMP_STATUS_PENDING      = 'pending';
    const MAILCHIMP_STATUS_SUBSCRIBED   = 'subscribed';
    const MAILCHIMP_STATUS_UNSUBSCRIBED = 'unsubscribed';
    const MAILCHIMP_STATUS_CLEANED      = 'cleaned';

    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var string */
    private $listId;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param string                 $listId
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, string $listId, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
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
//        $currentSubscriptionStatus = $this->getClientSubscriptionStatus($client);
//
//        if (
//            null === $currentSubscriptionStatus && self::MAILCHIMP_STATUS_SUBSCRIBED === $status
//            || null !== $currentSubscriptionStatus && false === in_array($currentSubscriptionStatus, [$status, self::MAILCHIMP_STATUS_CLEANED]) // Do not update status if email was cleaned
//        ) {
//            $mailChimpStatus = $status;
//
//            // If client has already unsubscribed, it cannot be subscribed again directly, email confirmation is needed
//            if (null !== $currentSubscriptionStatus && self::MAILCHIMP_STATUS_UNSUBSCRIBED === $currentSubscriptionStatus) {
//                $mailChimpStatus = self::MAILCHIMP_STATUS_PENDING;
//            }
//
//            $this->mailChimp->put('lists/' . $this->listId . '/members/' . md5(strtolower($client->getEmail())), [
//                'email_address'    => $client->getEmail(),
//                'email_type'       => 'html',
//                'status'           => $mailChimpStatus,
//                'merge_fields'     => [
//                    'FNAME' => $client->getFirstName(),
//                    'LNAME' => $client->getLastName(),
//                ],
//                'ip_signup'        => $ipAddress,
//                'timestamp_signup' => date('Y-m-d H:i:s'),
//                'ip_opt'           => $ipAddress,
//                'timestamp_opt'    => date('Y-m-d H:i:s'),
//            ]);
//
//            if (false !== $this->mailChimp->getLastError()) {
//                $this->logger->error('Could not update lender newsletter subscription. MailChimp API error: ' . $this->mailChimp->getLastError(), [
//                    'id_client' => $client->getIdClient(),
//                    'class'     => __CLASS__,
//                    'function'  => __FUNCTION__
//                ]);
//
//                return false;
//            }
//        }

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

    /**
     * @param Clients $client
     *
     * @return string|null
     */
    private function getClientSubscriptionStatus(Clients $client): ?string
    {
        $result = $this->mailChimp->get('lists/' . $this->listId . '/members/' . md5(strtolower($client->getEmail())), [
            'fields' => 'status'
        ]);

        if ($this->mailChimp->success() && isset($result['status'])) {
            return $result['status'];
        }

        return null;
    }
}
