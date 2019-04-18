<?php

declare(strict_types=1);

namespace Unilend\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Request, Response};
use Symfony\Component\Routing\{Annotation\Route, Router, RouterInterface};
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Entity\{Clients, Loans, Projects};
use Unilend\Service\ElectronicSignature;

/**
 * @Security("is_granted('ROLE_USER')")
 */
class SignatureController extends AbstractController
{
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger        = $logger;
    }

    /**
     * @Route("/signature/{hash}/{loanId}", name="demo_sign_contracts", requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}", "loanId": "\d+"})
     *
     * @param string                     $hash
     * @param int                        $loanId
     * @param ElectronicSignature        $signature
     * @param UserInterface|Clients|null $client
     * @param string                     $staticPath
     * @param RouterInterface            $router
     *
     * @return RedirectResponse
     */
    public function signContracts(
        string $hash,
        int $loanId,
        ElectronicSignature $signature,
        ?UserInterface $client,
        string $staticPath,
        RouterInterface $router
    ): RedirectResponse {
        $project = $this->entityManager->getRepository(Projects::class)->findOneBy(['hash' => $hash]);
        $loan    = $this->entityManager->getRepository(Loans::class)->find($loanId);

        if (null === $project || null === $loan || $loan->getProject() !== $project || $loan->getWallet()->getIdClient() !== $client) {
            return $this->redirectToRoute('demo_loans');
        }

        $documentContent = file_get_contents($staticPath . 'sous_participation.pdf');
        $url             = $signature->createSignatureRequest(
            $project->getIdClientSubmitter(),
            'Signature de votre contrat de sous-participation',
            'Contrat de sous-participation',
            base64_encode($documentContent),
            'pdf',
            '330',
            '520',
            $router->generate('demo_signature_confirmation', ['hash' => $project->getHash(), 'loanId' => $loanId], Router::ABSOLUTE_URL)
        );

        // @todo save envelope ID relation with loan in DB

        return new RedirectResponse($url);
    }

    /**
     * @Route(
     *     "/signature/confirmation/{hash}/{loanId}", name="demo_signature_confirmation",
     *     requirements={"hash": "[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}", "loanId": "\d+"}
     * )
     *
     * @param string                     $hash
     * @param int                        $loanId
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function signatureConfirmation(string $hash, int $loanId, Request $request, ?UserInterface $client): Response
    {
        $project = $this->entityManager->getRepository(Projects::class)->findOneBy(['hash' => $hash]);
        $loan    = $this->entityManager->getRepository(Loans::class)->find($loanId);

        if (null === $project || null === $loan || $loan->getProject() !== $project || $loan->getWallet()->getIdClient() !== $client) {
            return $this->redirectToRoute('demo_loans');
        }

        switch ($request->query->get('event')) {
            case ElectronicSignature::RECIPIENT_ACTION_SIGNING_COMPLETE:
                $loan->setStatus(Loans::STATUS_ACCEPTED);
                $this->entityManager->flush($loan);

                break;
        }

        // @todo retrieve document content from envelope ID saved in session
        // @todo check event status

        return $this->render('demo/signature_confirmation.html.twig');
    }
}
