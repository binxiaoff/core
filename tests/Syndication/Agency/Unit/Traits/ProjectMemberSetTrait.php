<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Unit\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use KLS\Core\Entity\Constant\LegalForm;
use KLS\Syndication\Agency\Entity\Agent;
use KLS\Syndication\Agency\Entity\AgentMember;
use KLS\Syndication\Agency\Entity\Borrower;
use KLS\Syndication\Agency\Entity\BorrowerMember;
use KLS\Syndication\Agency\Entity\Participation;
use KLS\Syndication\Agency\Entity\ParticipationMember;
use KLS\Syndication\Agency\Entity\ParticipationPool;
use KLS\Syndication\Agency\Entity\Project;
use KLS\Test\Core\Unit\Traits\PropertyValueTrait;
use KLS\Test\Core\Unit\Traits\UserStaffTrait;
use ReflectionException;

trait ProjectMemberSetTrait
{
    use PropertyValueTrait;
    use UserStaffTrait;
    use AgencyProjectTrait;

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createAgentMember(): AgentMember
    {
        $staff = $this->createStaff();

        $agentMember = new AgentMember(
            new Agent($this->createAgencyProject($staff), $staff->getCompany()),
            $staff->getUser()
        );
        $this->forcePropertyValue($agentMember, 'id', 1);

        return $agentMember;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createBorrowerMember(): BorrowerMember
    {
        $staff = $this->createStaff();

        $borrowerMember = new BorrowerMember(
            new Borrower(
                $this->createAgencyProject($staff),
                'Borrower Name',
                LegalForm::SA,
                'Head office',
                '042424242'
            ),
            $staff->getUser()
        );
        $this->forcePropertyValue($borrowerMember, 'id', 2);

        return $borrowerMember;
    }

    /**
     * @throws ReflectionException
     * @throws Exception
     */
    private function createParticipationMember(): ParticipationMember
    {
        $staff = $this->createStaff();

        $participationMember = new ParticipationMember(
            new Participation(
                new ParticipationPool($this->createAgencyProject($staff), false),
                $this->createStaff()->getCompany()
            ),
            $staff->getUser()
        );
        $this->forcePropertyValue($participationMember, 'id', 3);

        return $participationMember;
    }

    /**
     * @param array<string,int> $projectMemberData string:projectMemberClass, int:projectMemberNb
     *
     * @throws ReflectionException
     * @throws Exception
     */
    private function createProjectWithMembers(array $projectMemberData): Project
    {
        $staff   = $this->createStaff();
        $project = $this->createAgencyProject($staff);

        // init agent
        $this->forcePropertyValue($project, 'agent', new Agent($project, $staff->getCompany()));

        // init borrowers
        $borrowersCollection = new ArrayCollection();
        $borrowersCollection->add(new Borrower(
            $project,
            'Borrower Name',
            LegalForm::SA,
            'Head office',
            '042424242'
        ));
        $this->forcePropertyValue($project, 'borrowers', $borrowersCollection);

        // init participationPools primary
        $participationPoolPrimary = $project->getParticipationPools()[false];
        $participationPoolPrimary->addParticipation(new Participation($participationPoolPrimary, $staff->getCompany()));

        foreach ($projectMemberData as $projectMemberClass => $projectMemberNb) {
            if (AgentMember::class === $projectMemberClass) {
                foreach (\range(1, $projectMemberNb) as $index) {
                    $project->getAgent()->addMember($this->createAgentMember());
                }
            }

            if (BorrowerMember::class === $projectMemberClass) {
                foreach ($project->getBorrowers() as $borrower) {
                    foreach (\range(1, $projectMemberNb) as $index) {
                        $borrower->addMember($this->createBorrowerMember());
                    }
                }
            }

            if (ParticipationMember::class === $projectMemberClass) {
                /** @var Participation $participation */
                foreach ($participationPoolPrimary->getParticipations() as $participation) {
                    foreach (\range(1, $projectMemberNb) as $index) {
                        $participation->addMember($this->createParticipationMember());
                    }
                }
            }
        }

        return $project;
    }
}
