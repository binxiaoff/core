<?php

declare(strict_types=1);

namespace Unilend\Agency\Command;

use JsonException;
use Swift_Mailer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Unilend\Agency\Entity\AgentMember;
use Unilend\Agency\Entity\BorrowerMember;
use Unilend\Agency\Entity\Covenant;
use Unilend\Agency\Entity\Project;
use Unilend\Agency\Entity\Term;
use Unilend\Agency\Repository\TermRepository;
use Unilend\Core\Entity\User;
use Unilend\Core\SwiftMailer\MailjetMessage;

class RemindTermCommand extends Command
{
    protected static $defaultName = 'kls:agency:remind';

    private Swift_Mailer $mailer;
    private TermRepository $termRepository;

    public function __construct(Swift_Mailer $mailer, TermRepository $termRepository, string $name = null)
    {
        parent::__construct($name);

        $this->mailer         = $mailer;
        $this->termRepository = $termRepository;
    }

    protected function configure(): void
    {
        $this->setDescription('Send reminders for terms (1 day before and 1 week before)');
    }

    /**
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $borrowerUsersByProject = [];
        $agentUsersByProject    = [];

        $terms = $this->termRepository->findBy([
            'endDate' => [
                (new \DateTimeImmutable('+ 1 day'))->format('Y-m-d'),
                (new \DateTimeImmutable('+ 1 week'))->format('Y-m-d'),
            ],
            'sharingDate' => null,
        ]);

        foreach ($terms as $term) {
            $project   = $term->getProject();
            $projectId = $project->getId();

            $borrowerUsersByProject[$projectId] = $borrowerUsersByProject[$projectId] ?? $this->getBorrowerUsers($project);

            // This also disable reminder email for borrower as there is no pendingBorrowerInput for control nature terms
            if ($term->isPendingBorrowerInput()) {
                foreach ($borrowerUsersByProject[$project->getId()] as $user) {
                    $this->send($this->createMessage($term, $user, MailjetMessage::TEMPLATE_AGENCY_REMIND_TERM_BORROWER), $output);
                }
            }

            $agentUsersByProject[$projectId] = $agentUsersByProject[$projectId] ?? $this->getAgentUsers($project);

            foreach ($agentUsersByProject[$projectId] as $user) {
                $this->send($this->createMessage($term, $user, MailjetMessage::TEMPLATE_AGENCY_REMIND_TERM_AGENT), $output);
            }
        }

        $terms = $this->termRepository->findBy(['startDate' => new \DateTimeImmutable('today'), 'sharingDate' => null]);

        foreach ($terms as $term) {
            $project   = $term->getProject();
            $projectId = $project->getId();

            $borrowerUsersByProject[$projectId] = $borrowerUsersByProject[$projectId] ?? $this->getBorrowerUsers($project);

            if (Covenant::NATURE_CONTROL !== $term->getNature()) {
                foreach ($borrowerUsersByProject[$projectId] as $user) {
                    $this->send($this->createMessage($term, $user, MailjetMessage::TEMPLATE_AGENCY_REMIND_TERM_BORROWER), $output);
                }
            }
        }

        return Command::SUCCESS;
    }

    private function getBorrowerUsers(Project $project): iterable
    {
        $users = [];

        foreach ($project->getBorrowers() as $borrower) {
            $users = [...$users, ...$borrower->getMembers()->map(fn (BorrowerMember $borrowerMember) => $borrowerMember->getUser())->toArray()];
        }

        return \array_unique($users);
    }

    private function getAgentUsers(Project $project): iterable
    {
        return \array_unique($project->getAgent()->getMembers()->map(fn (AgentMember $member) => $member->getUser())->toArray());
    }

    /**
     * @throws JsonException
     */
    private function createMessage(Term $term, User $user, int $templateId): MailjetMessage
    {
        return (new MailjetMessage())
            ->setTo($user->getEmail())
            ->setTemplateId($templateId)
            ->setVars([
                'covenantName'         => $term->getCovenant()->getName(),
                'covenantNature'       => $term->getCovenant()->getNature(),
                'projectRiskGroupName' => $term->getProject()->getRiskGroupName(),
                'projectTitle'         => $term->getProject()->getTitle(),
                'termEndDate'          => $term->getEndDate()->format('d/m/Y'),
                'termStartDate'        => $term->getStartDate()->format('d/m/Y'),
                'firstName'            => $user->getFirstName(),
                'lastName'             => $user->getLastName(),
            ])
        ;
    }

    private function send(MailjetMessage $mailjetMessage, OutputInterface $output): void
    {
        $vars = $mailjetMessage->getVars();

        if ($output->isVerbose()) {
            $output->writeln(\sprintf('Sending an email to %s for %s', \implode(' and ', \array_keys($mailjetMessage->getTo())), $vars['covenantName']));
        }

        if ($output->isVeryVerbose()) {
            $output->writeln('Variables for message :');
            foreach ($vars as $key => $value) {
                $output->writeln("\t{$key} : {$value}");
            }
        }

        $this->mailer->send($mailjetMessage);

        if ($output->isVerbose()) {
            $output->writeln('Email sent');
        }
    }
}
