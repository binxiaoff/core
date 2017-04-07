<?php

namespace Unilend\Bundle\CoreBusinessBundle\Service;

use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints\Bic;
use Symfony\Component\Validator\Constraints\Iban;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Attachment;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager as EntityManagerSimulator;

class BankAccountManager
{
    /** @var EntityManager */
    private $entityManager;

    /** @var  EntityManagerSimulator */
    private $entityManagerSimulator;

    /** @var  LenderManager */
    private $lenderManager;

    /** @var  LoggerInterface */
    private $logger;

    /** @var ValidatorInterface */
    private $validator;

    /**
     * BankAccountManager constructor.
     *
     * @param EntityManager          $entityManager
     * @param EntityManagerSimulator $entityManagerSimulator
     * @param LenderManager          $lenderManager
     * @param LoggerInterface        $logger
     * @param ValidatorInterface     $validator
     */
    public function __construct(
        EntityManager $entityManager,
        EntityManagerSimulator $entityManagerSimulator,
        LenderManager $lenderManager,
        LoggerInterface $logger,
        ValidatorInterface $validator
    ) {
        $this->entityManager          = $entityManager;
        $this->entityManagerSimulator = $entityManagerSimulator;
        $this->lenderManager          = $lenderManager;
        $this->logger                 = $logger;
        $this->validator              = $validator;
    }


    /**
     * When we save a different IBAN (or no bank information) for a client, we archive the old one, and create the new on.
     * Otherwise (if IBAN is the same), we update the existing bank information, and if the bic is modified, we put the bank information in "pending".
     *
     * When we create a new line in bank_account, the default status is "pending".
     * And before the creation, we archive all other "pending" belong to the client.
     *
     * When we update a line, if the BIC is changed, we put it in "pending", to validate it again.
     *
     * @param Clients         $client
     * @param string          $bic
     * @param string          $iban
     * @param Attachment|null $attachment
     *
     * @return BankAccount
     *
     * @throws \Exception
     */
    public function saveBankInformation(Clients $client, $bic, $iban, Attachment $attachment = null)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $iban           = strtoupper(preg_replace('/\s+/', '', $iban));
            $bic            = strtoupper(preg_replace('/\s+/', '', $bic));
            $ibanViolations = $this->validator->validate($iban, new Iban());
            if (0 < $ibanViolations->count()) {
                throw new \InvalidArgumentException('IBAN is not valid');
            }
            $bicViolations = $this->validator->validate($bic, new Bic());
            if (0 < $bicViolations->count()) {
                throw new \InvalidArgumentException('BIC is not valid');
            }

            $bankAccountRepository = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount');
            $bankAccount           = $bankAccountRepository->findOneBy(['idClient' => $client, 'iban' => $iban]);
            if ($bankAccount instanceof BankAccount) {
                if ($bic !== $bankAccount->getBic()) {
                    $bankAccount->setDateValidated(null);
                    $bankAccount->setDatePending(new \DateTime());
                    $this->updateLegacyBankAccount($client, $bankAccount);
                }
                $bankAccount->setBic($bic);
                $bankAccount->setAttachment($attachment);
                $bankAccount->setDateArchived(null);
                $this->entityManager->flush($bankAccount);
            } else {
                $pendingBankAccount = $bankAccountRepository->findBy(['idClient' => $client, 'dateValidated' => null, 'dateArchived' => null]);
                foreach ($pendingBankAccount as $bankAccountToArchive) {
                    $bankAccountToArchive->setDateArchived(new \DateTime());
                    $this->entityManager->flush($bankAccountToArchive);
                }
                $bankAccount = new BankAccount();
                $bankAccount->setIdClient($client)
                            ->setIban($iban)
                            ->setBic($bic)
                            ->setAttachment($attachment);
                $this->entityManager->persist($bankAccount);
                $this->entityManager->flush($bankAccount);

                $this->updateLegacyBankAccount($client, $bankAccount);
            }
            $this->entityManager->getConnection()->commit();

            return $bankAccount;
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }

    /**
     * @param Clients     $client
     * @param BankAccount $bankAccount
     */
    private function updateLegacyBankAccount(Clients $client, BankAccount $bankAccount)
    {
        /** @var \lenders_accounts $lenderAccount */
        $lenderAccount = $this->entityManagerSimulator->getRepository('lenders_accounts');
        $lenderAccount->get($client->getIdClient(), 'id_client_owner');
        $lenderAccount->bic   = $bankAccount->getBic();
        $lenderAccount->iban  = $bankAccount->getIban();
        $lenderAccount->motif = $this->lenderManager->getLenderPattern($client);
        $lenderAccount->update();
    }

    /**
     * @param BankAccount $bankAccount
     *
     * @throws \Exception
     */
    public function validateBankAccount(BankAccount $bankAccount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $previousValidBankAccount = $this->entityManager->getRepository('UnilendCoreBusinessBundle:BankAccount')->getClientValidatedBankAccount($bankAccount->getIdClient());
            if ($previousValidBankAccount !== $bankAccount) {
                if ($previousValidBankAccount) {
                    $previousValidBankAccount->setDateArchived(new \DateTime());
                    $this->entityManager->flush($previousValidBankAccount);
                }
                $bankAccount->setDateValidated(new \DateTime());
                $bankAccount->setDateArchived(null);
                $this->entityManager->flush($bankAccount);
            }
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $exception) {
            $this->entityManager->getConnection()->rollBack();
            throw $exception;
        }
    }
}
