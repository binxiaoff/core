<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{BinaryFileResponse, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\Clients;
use Unilend\Entity\Loans;
use Unilend\Bundle\CoreBusinessBundle\Service\Document\LoanContractGenerator;

class DocumentController extends Controller
{
    const ERROR_CANNOT_FIND_LOAN   = 'cannot-find-loan';
    const ERROR_CANNOT_FIND_CLIENT = 'cannot-find-client';
    const ERROR_WRONG_CLIENT_HASH  = 'wrong-client-hash';
    const ERROR_ACCESS_DENIED      = 'access-denied';
    const ERROR_EXCEPTION_OCCURRED = 'exception-occurred';
    const ERROR_UNKNOWN            = 'unknown';

    /**
     * @Route("/pdf/contrat/{clientHash}/{idLoan}", name="loan_contract_pdf", requirements={"clientHash": "[0-9a-f-]{32,36}", "idLoan": "\d+"})
     * @Security("has_role('ROLE_LENDER')")
     *
     * @param UserInterface|Clients|null $client
     * @param string                     $clientHash
     * @param int                        $idLoan
     *
     * @return Response
     */
    public function loanContractPdfAction(?UserInterface $client, string $clientHash, int $idLoan): Response
    {
        /** @var Loans $loan */
        $loan = $this->get('doctrine.orm.entity_manager')
            ->getRepository(Loans::class)
            ->find($idLoan);

        if (null === $loan) {
            return $this->getLoanErrorResponse(self::ERROR_CANNOT_FIND_LOAN, $idLoan);
        }

        if (null === $loan->getWallet() || null === $loan->getWallet()->getIdClient()) {
            return $this->getLoanErrorResponse(self::ERROR_CANNOT_FIND_CLIENT, $loan);
        }

        if ($clientHash !== $loan->getWallet()->getIdClient()->getHash()) {
            return $this->getLoanErrorResponse(self::ERROR_WRONG_CLIENT_HASH, $loan);
        }

        if (null === $client || $client->getIdClient() !== $loan->getWallet()->getIdClient()->getIdClient()) {
            return $this->getLoanErrorResponse(self::ERROR_ACCESS_DENIED, $loan);
        }

        $loanContractGenerator = $this->get(LoanContractGenerator::class);

        try {
            if (false === $loanContractGenerator->exists($loan)) {
                $loanContractGenerator->generate($loan);
            }

            $filePath = $loanContractGenerator->getPath($loan);
        } catch (\Exception $exception) {
             return $this->getLoanErrorResponse(self::ERROR_EXCEPTION_OCCURRED, $loan, $exception);
        }

        return new BinaryFileResponse($filePath, 200, [
            'Content-Type'        => $loanContractGenerator->getContentType(),
            'Content-Length'      => filesize($filePath),
            'Content-Disposition' => 'attachement; filename="CONTRAT-UNILEND-' . $loan->getProject()->getSlug() . '-' . $loan->getIdLoan() . '.pdf"'
        ]);
    }

    /**
     * @param string          $error
     * @param int|Loans|null  $loan
     * @param \Exception|null $exception
     *
     * @return Response
     */
    private function getLoanErrorResponse(string $error, $loan = null, ?\Exception $exception = null): Response
    {
        $context = [];

        switch ($error) {
            case self::ERROR_CANNOT_FIND_LOAN:
                $message = 'Loan contract ' . $loan . ' could not be displayed: cannot find loan';
                $context = ['id_loan' => $loan];
                break;
            case self::ERROR_CANNOT_FIND_CLIENT:
                $message = 'Loan contract ' . $loan->getIdLoan() . ' could not be displayed: cannot find client';
                $context = ['id_loan' => $loan->getIdLoan()];
                break;
            case self::ERROR_WRONG_CLIENT_HASH:
                $message = 'Loan contract ' . $loan->getIdLoan() . ' could not be displayed: URL does not match client hash';
                $context = [
                    'id_loan'   => $loan->getIdLoan(),
                    'id_client' => $loan->getWallet()->getIdClient()->getIdClient()
                ];
                break;
            case self::ERROR_ACCESS_DENIED:
                $message = 'Loan contract ' . $loan->getIdLoan() . ' could not be displayed: access denied';
                $context = [
                    'id_loan'   => $loan->getIdLoan(),
                    'id_client' => $loan->getWallet()->getIdClient()->getIdClient()
                ];
                break;
            case self::ERROR_EXCEPTION_OCCURRED:
                $message = 'Loan contract could not be displayed: exception occurend - Message: ' . $exception->getMessage();
                $context = [
                    'id_loan'   => $loan->getIdLoan(),
                    'id_client' => $loan->getWallet()->getIdClient()->getIdClient(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine()
                ];
                break;
            default:
                $message = $error;
                $error   = self::ERROR_UNKNOWN;
                break;
        }

        $this->get('logger')->error($message, $context);

        $translator = $this->get('translator');

        return $this->render('exception/error.html.twig', [
            'errorTitle'   => $translator->trans('loan-contract-download_' . $error . '-error-title'),
            'errorDetails' => $translator->trans('loan-contract-download_error-details-contact-link', ['%contactUrl%' => $this->generateUrl('contact')])
        ])->setStatusCode(Response::HTTP_NOT_FOUND);
    }
}
