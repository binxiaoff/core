<?php

declare(strict_types=1);

namespace Unilend\Service;

use Doctrine\ORM\{EntityManagerInterface};
use NumberFormatter;
use Swift_Mailer;
use Symfony\Component\Routing\RouterInterface;
use Twig\Error\{LoaderError, RuntimeError, SyntaxError};
use Unilend\Entity\{AttachmentSignature, Clients, Project, ProjectComment, ProjectParticipationContact, Staff, Tranche, TrancheOffer};
use Unilend\Repository\StaffRepository;
use Unilend\SwiftMailer\TemplateMessageProvider;

class MailerManager
{
    /** @var TemplateMessageProvider */
    private $messageProvider;
    /** @var Swift_Mailer */
    private $mailer;
    /** @var EntityManagerInterface */
    private $entityManager;
    /** @var RouterInterface */
    private $router;
    /** @var NumberFormatter */
    private $percentageFormatter;
    /** @var NumberFormatter */
    private $numberFormatter;
    /** @var StaffRepository */
    private $staffRepository;

    /**
     * @param TemplateMessageProvider $messageProvider
     * @param Swift_Mailer            $mailer
     * @param EntityManagerInterface  $entityManager
     * @param RouterInterface         $router
     * @param NumberFormatter         $numberFormatter
     * @param NumberFormatter         $percentageFormatter
     * @param StaffRepository         $staffRepository
     */
    public function __construct(
        TemplateMessageProvider $messageProvider,
        Swift_Mailer $mailer,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        NumberFormatter $numberFormatter,
        NumberFormatter $percentageFormatter,
        StaffRepository $staffRepository
    ) {
        $this->messageProvider     = $messageProvider;
        $this->mailer              = $mailer;
        $this->entityManager       = $entityManager;
        $this->percentageFormatter = $percentageFormatter;
        $this->numberFormatter     = $numberFormatter;
        $this->router              = $router;
        $this->staffRepository     = $staffRepository;
    }

    /**
     * @param ProjectComment $comment
     * @param Clients[]      $recipients
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendProjectCommentAdded(ProjectComment $comment, array $recipients): int
    {
        return 0; // TODO redo the mails
        $sent     = 0;
        $project  = $comment->getProject();
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL) . '#article-discussions',
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();

                return 1;
            }
        }

        return $sent;
    }

    /**
     * @param TrancheOffer $trancheOffer
     * @param Clients[]    $recipients
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendTrancheOfferSubmitted(TrancheOffer $trancheOffer, array $recipients): int
    {
        $sent     = 0;
        $project  = $trancheOffer->getTranche()->getProject();
        $keywords = [
            'firstName'              => '',
            'projectUrl'             => $this->router->generate('edit_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName'            => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'submitterName'          => $trancheOffer->getAddedBy()->getClient()->getName(),
            'trancheOfferAmount'     => $this->numberFormatter->format($trancheOffer->getMoney()->getAmount()),
            'trancheOfferRateIndex'  => $trancheOffer->getRate()->getIndexType(),
            'trancheOfferMarginRate' => $this->percentageFormatter->format($trancheOffer->getRate()->getMargin()),
        ];

        foreach ($recipients as $recipient) {
            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();

                return 1;
            }
        }

        return $sent;
    }

    /**
     * @param TrancheOffer $trancheOffer
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendTrancheOfferAcceptedRejected(TrancheOffer $trancheOffer): int
    {
        return 0; // TODO redo the mails
        $recipient = $trancheOffer->getAddedBy();

        if (empty($recipient->getEmail())) {
            return 0;
        }

        $project  = $trancheOffer->getTranche()->getProject();
        $mailType = TrancheOffer::STATUS_ACCEPTED === $trancheOffer->getStatus() ? 'tranche-offer-accepted' : 'tranche-offer-rejected';

        return 1;
    }

    /**
     * @param Project $project
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendProjectFundingEnd(Project $project): int
    {
        return 0; // TODO redo the mails
        $keywords = [
            'firstName'   => '',
            'projectUrl'  => $this->router->generate('lender_project_details', ['hash' => $project->getHash()], RouterInterface::ABSOLUTE_URL),
            'projectName' => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
        ];

        $sent     = 0;
        $tranches = $this->entityManager->getRepository(Tranche::class)->findBy(['project' => $project]);
        $loans    = $this->entityManager->getRepository(TrancheOffer::class)->findBy([
            'tranche' => $tranches,
            'status'  => TrancheOffer::STATUS_ACCEPTED,
        ]);

        foreach ($loans as $loan) {
            // @todo change when all roles are defined
            $recipient = $loan->getLender()->getIdClientOwner();

            if (false === empty($recipient->getEmail())) {
                $keywords['firstName'] = $recipient->getFirstName();

                ++$sent;
            }
        }

        return $sent;
    }

    /**
     * @param Project             $project
     * @param AttachmentSignature $signature
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int
     */
    public function sendElectronicSignature(Project $project, AttachmentSignature $signature): int
    {
        $keywords = [
            'firstName'    => $signature->getSignatory()->getFirstName(),
            'projectName'  => $project->getBorrowerCompany()->getName() . ' / ' . $project->getTitle(),
            'signatureUrl' => $this->router->generate('signature_sign', ['attachment' => $signature->getAttachment()->getId()], RouterInterface::ABSOLUTE_URL),
        ];

        return 1;
    }

    /**
     * @param ProjectParticipationContact $projectParticipationContact
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     *
     * @return int|string
     */
    public function sendRequestToAssignRights(ProjectParticipationContact $projectParticipationContact)
    {
        $sent = 0;

        $projectParticipation = $projectParticipationContact->getProjectParticipation();
        $guest                = $projectParticipationContact->getClient()->getFirstName() . ' ' . $projectParticipationContact->getClient()->getLastName();

        $keywords = [
            'firstName'   => '',
            'guest'       => $guest,
            'projectName' => $projectParticipation->getProject()->getTitle(),
        ];

        $companyStaffs = $this->staffRepository->findBy([
            'company' => $projectParticipation->getCompany(),
        ]);

        foreach ($companyStaffs as $companyStaff) {
            if (in_array(Staff::DUTY_STAFF_ADMIN, $companyStaff->getRoles())) {
                $recipient             = $companyStaff->getClient();
                $keywords['firstName'] = $recipient->getFirstName();
            }
        }

        return $sent;
    }
}
