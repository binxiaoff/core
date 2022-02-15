<?php

declare(strict_types=1);

namespace KLS\Syndication\Arrangement\Service\Project\MailNotifier;

use InvalidArgumentException;
use KLS\Core\Mailer\MailjetMessage;
use KLS\Core\MessageHandler\File\FileUploadedNotifierInterface;
use KLS\Syndication\Arrangement\Entity\ProjectStatus;
use KLS\Syndication\Arrangement\Repository\ProjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class ProjectUploadNotifier implements FileUploadedNotifierInterface
{
    private MailerInterface   $mailer;
    private RouterInterface   $router;
    private ProjectRepository $projectRepository;
    private LoggerInterface   $logger;

    public function __construct(
        MailerInterface $mailer,
        RouterInterface $router,
        ProjectRepository $projectRepository,
        LoggerInterface $logger
    ) {
        $this->mailer            = $mailer;
        $this->router            = $router;
        $this->projectRepository = $projectRepository;
        $this->logger            = $logger;
    }

    public function notify(array $context): int
    {
        if (false === $this->supports($context)) {
            return 0;
        }

        $project = $this->projectRepository->find($context['projectId']);

        if (null === $project) {
            throw new InvalidArgumentException(
                \sprintf('The project with id %d does not exist', $context['projectId'])
            );
        }

        $sent = 0;

        if (ProjectStatus::STATUS_INTEREST_EXPRESSION > $project->getCurrentStatus()->getStatus()) {
            return $sent;
        }
        $templateId = MailjetMessage::TEMPLATE_PROJECT_FILE_UPLOADED;
        foreach ($project->getNotifiableParticipations() as $participation) {
            if (
                $participation->getParticipant() !== $project->getSubmitterCompany()
                && (
                    $participation->getParticipant()->hasSigned()
                    || false === $participation->getParticipant()->isCAGMember()
                )
            ) {
                foreach ($participation->getActiveProjectParticipationMembers() as $activeProjectParticipationMember) {
                    $mailAddress = $activeProjectParticipationMember->getStaff()->getUser()->getEmail();

                    try {
                        $message = (new MailjetMessage())
                            ->to($mailAddress)
                            ->setTemplateId($templateId)
                            ->setVars([
                                'front_viewParticipation_URL' => $this->router->generate(
                                    'front_viewParticipation',
                                    ['projectParticipationPublicId' => $participation->getPublicId()],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                ),
                                'client_firstName' => $activeProjectParticipationMember
                                    ->getStaff()
                                    ->getUser()
                                    ->getFirstName() ?? '',
                                'project_arranger'      => $project->getSubmitterCompany()->getDisplayName(),
                                'project_title'         => $project->getTitle(),
                                'project_riskGroupName' => $project->getRiskGroupName(),
                            ])
                        ;
                        $this->mailer->send($message);
                        ++$sent;
                    } catch (\Throwable $throwable) {
                        $this->logger->error(
                            \sprintf(
                                'Email sending failed for %s with template id %d. Error: %s',
                                $mailAddress,
                                $templateId,
                                $throwable->getMessage()
                            ),
                            ['throwable' => $throwable]
                        );
                    }
                }
            }
        }

        return $sent;
    }

    private function supports(array $context): bool
    {
        if (empty($context['projectId'])) {
            return false;
        }

        return true;
    }
}
