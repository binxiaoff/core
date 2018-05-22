<?php

namespace Unilend\Bundle\CommandBundle\Command\Dev;

use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;

class MailChimpGdprCatchUpCommand extends ContainerAwareCommand
{
    const ERROR_CODE_MAILCHIMP_API_ERROR     = 1;
    const ERRPR_CODE_INVALID_RESPONSE_FORMAT = 2;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('dev:mailchimp:gdpr_catch_up')
            ->setDescription('Catchup MailChimp GDPR consent back to Unilend platform');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mailChimp = $this->getContainer()->get('welp_mailchimp.mailchimp_master');
        $listId    = $this->getContainer()->getParameter('mailchimp.list_id');
        $segmentId = $this->getContainer()->getParameter('mailchimp.gdpr_segment_id');
        $response  = $mailChimp->get('lists/' . $listId . '/segments/' . $segmentId . '/members?fields=total_items,members.email_address,members.status&count=100000');

        if (false !== $mailChimp->getLastError()) {
            $this->getContainer()->get('logger')->error('Could not catchup MailChimp GDPR consent. MailChimp API error: ' . $mailChimp->getLastError(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            $output->writeln('MailChimp API error: ' . $mailChimp->getLastError());

            return self::ERROR_CODE_MAILCHIMP_API_ERROR;
        }

        if (false === is_array($response) || false === isset($response['members']) || false === is_array($response['members'])) {
            $this->getContainer()->get('logger')->error('Could not catchup MailChimp GDPR consent. MailChimp API error: ' . $mailChimp->getLastError(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__
            ]);

            $output->writeln('Invalid MailChimp API response format');

            return self::ERRPR_CODE_INVALID_RESPONSE_FORMAT;
        }

        $entitiesToFlush  = 0;
        $entityManager    = $this->getContainer()->get('doctrine.orm.entity_manager');
        $clientRepository = $entityManager->getRepository('UnilendCoreBusinessBundle:Clients');

        foreach ($response['members'] as $member) {
            /** @var Clients[] $clients */
            $clients = $clientRepository->findBy(['email' => $member['email_address']]);

            foreach ($clients as $client) {
                $optIn = 'subscribed' === $member['status'] ? Clients::NEWSLETTER_OPT_IN_ENROLLED : Clients::NEWSLETTER_OPT_IN_NOT_ENROLLED;

                if ($optIn !== $client->getOptin1()) {
                    $client->setOptin1($optIn);

                    ++$entitiesToFlush;

                    if (0 === $entitiesToFlush % 100) {
                        try {
                            $entityManager->flush();
                        } catch (OptimisticLockException $exception) {
                            $this->getContainer()->get('logger')->error('Could not update lenders newsletter subscription. Error: ' . $exception->getMessage(), [
                                'class'    => __CLASS__,
                                'function' => __FUNCTION__,
                                'file'     => $exception->getFile(),
                                'line'     => $exception->getLine()
                            ]);
                        }
                    }
                }
            }
        }

        try {
            $entityManager->flush();
        } catch (OptimisticLockException $exception) {
            $this->getContainer()->get('logger')->error('Could not update lenders newsletter subscription. Error: ' . $exception->getMessage(), [
                'class'     => __CLASS__,
                'function'  => __FUNCTION__,
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine()
            ]);
        }

        $output->writeln('Clients updated: ' . $entitiesToFlush);

        return 0;
    }
}
