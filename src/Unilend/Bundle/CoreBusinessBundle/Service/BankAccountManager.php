<?php


namespace Unilend\Bundle\CoreBusinessBundle\Service;


use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Repository\BankAccountRepository;
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
     * @param Clients $clientEntity
     * @param string  $bic
     * @param string  $iban
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function saveBankInformation(Clients $clientEntity, $bic, $iban)
    {
        /** @var BankAccountRepository $bankAccountRepository */
        $bankAccountRepository = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount');

        /** @var BankAccount $bankAccount */
        $bankAccount = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount')->findOneBy(['idClient' => $clientEntity, 'iban' => $iban]);

        if (null !== $bankAccount) {
            if ($bic !== $bankAccount->getBic()) {
                $bankAccount->setBic($bic);
                $bankAccount->setStatus(BankAccount::STATUS_PENDING);
                $bankAccount->setDatePending(new \DateTime('NOW'));
                $this->updateLegacyBankAccount($clientEntity, $bankAccount);
                $this->em->flush();
                return $bankAccount;
            }
        }

        $this->em->getConnection()->beginTransaction();
        try {

            $newBankAccount = $bankAccountRepository->saveBankAccount($clientEntity->getIdClient(), $bic, $iban);
            $this->updateLegacyBankAccount($clientEntity, $newBankAccount);

            $this->em->flush();
            $this->em->getConnection()->commit();
            return $newBankAccount;

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
        $now = new \DateTime('NOW');

        if (BankAccount::STATUS_PENDING == $newBankAccount->getStatus()) {
            $newBankAccount->setStatus(BankAccount::STATUS_VALIDATED);
            $newBankAccount->setDateValidated($now);
        }

        /** @var BankAccount[] $existingBankAccounts */
        $existingBankAccounts = $this->em->getRepository('UnilendCoreBusinessBundle:BankAccount')->getPreviousBankAccounts($newBankAccount->getDatePending(), $newBankAccount->getIdClient());

        if (false === empty($existingBankAccounts)) {
            foreach ($existingBankAccounts as $bankAccount) {
                if ($bankAccount !== $newBankAccount && $bankAccount->getStatus() !== BankAccount::STATUS_ARCHIVED) {
                    $bankAccount->setStatus(BankAccount::STATUS_ARCHIVED);
                    $bankAccount->setDateArchived($now);
                }
            }
        }

        $this->em->flush();
    }
}
