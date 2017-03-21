<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class BankAccountManager
{
    /** @var  EntityManager */
    private $em;
    /** @var  EntityManagerSimulator */
    private $entityManager;
    /** @var  LenderManager */
    private $lenderManager;
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(
        EntityManager $em,
        EntityManagerSimulator $entityManager,
        LenderManager $lenderManager,
        LoggerInterface $logger
    ) {
        $this->em            = $em;
        $this->entityManager = $entityManager;
        $this->lenderManager = $lenderManager;
        $this->logger        = $logger;
    }


    /**
     * @param Clients $client
     * @param string  $bic
     * @param string  $iban
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function saveBankInformation(Clients $client, $bic, $iban)
    {
        $this->em->getConnection()->beginTransaction();
        try {
            $bic  = preg_replace('/\s+/', '', $bic);
            $iban = preg_replace('/\s+/', '', $iban);
            $bankAccount = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount')->findOneBy(['idClient' => $client, 'iban' => $iban]);

            if ($bankAccount instanceof BankAccount) {
                if ($bic !== $bankAccount->getBic()) {
                    $bankAccount->setBic($bic);
                    $bankAccount->setDateValidated(null);
                    $bankAccount->setDatePending(new \DateTime('NOW'));
                    $this->updateLegacyBankAccount($client, $bankAccount);
                }
                $bankAccount->setDateArchived(null);
                $this->em->flush($bankAccount);
            } else {
                $this->archiveBankAccounts($client);
                $bankAccount = new BankAccount();
                $bankAccount->setIdClient($client)
                            ->setIban($iban)
                            ->setBic($bic);
                $this->em->persist($bankAccount);
                $this->em->flush($bankAccount);

                $this->updateLegacyBankAccount($client, $bankAccount);
                $this->em->getConnection()->commit();
            }

            return $bankAccount;
        } catch (\Exception $e) {
            $this->em->getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * @param Clients     $client
     * @param BankAccount $bankAccount
     */
    private function updateLegacyBankAccount(Clients $client, BankAccount $bankAccount)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManager->getRepository('lenders_accounts');
        $lenderAccount->get($client->getIdClient(), 'id_client_owner');
        $lenderAccount->bic   = $bankAccount->getBic();
        $lenderAccount->iban  = $bankAccount->getIban();
        $lenderAccount->motif = $this->lenderManager->getLenderPattern($client);
        $lenderAccount->update();
    }

    /**
     * @param BankAccount $newBankAccount
     */
    public function validateBankAccount(BankAccount $newBankAccount)
    {
        $newBankAccount->setDateValidated(new \DateTime());
        $newBankAccount->setDateArchived(null);
        $this->em->flush($newBankAccount);
    }

    /**
     * @param Clients $client
     */
    private function archiveBankAccounts(Clients $client)
    {
        $existingBankAccounts = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount')->findBy(['idClient' => $client, 'dateArchived' => null]);
        $now                  = new \DateTime();
        if (false === empty($existingBankAccounts)) {
            foreach ($existingBankAccounts as $bankAccount) {
                $bankAccount->setDateArchived($now);
            }
        }
        $this->em->flush();
    }
}
