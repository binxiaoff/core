<?php

namespace Unilend\Test\Unit\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\Clients;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Embeddable\Money;
use Unilend\Core\Entity\MarketSegment;
use Unilend\Core\Entity\Staff;
use Unilend\Entity\Project;
use Unilend\Entity\ProjectParticipation;
use Unilend\Entity\ProjectParticipationMember;
use Unilend\Security\Voter\ProjectParticipationMemberVoter;
use Unilend\Service\ProjectParticipation\ProjectParticipationManager;

class ProjectParticipationMemberVoterTest extends AbstractVoterTestCase
{

    /**
     * @var ProjectParticipation
     */
    private ProjectParticipation $participation;

    /**
     * Setup the participation used for the test
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        // Prepare the data
        $company = new Company('A', 'a');
        $arrangerStaff = $this->getMockBuilder(Staff::class)
            ->disableOriginalConstructor()
            ->getMock();
        $client = new Clients('email@demo.fr');
        $staff = new Staff($company, $client, $arrangerStaff);
        $client->setCurrentStaff($staff);
        $company->addStaff($staff);
        $project = new Project($arrangerStaff, 'risk1', new Money('EUR', '10000'), new MarketSegment());
        $this->participation = new ProjectParticipation($company, $project, $arrangerStaff);

        // Creates the voter
        $authorizationChecker = $this->getMockBuilder(AuthorizationCheckerInterface::class)->disableOriginalConstructor()->getMock();
        $projectParticipationManager = $this->getMockBuilder(ProjectParticipationManager::class)->disableOriginalConstructor()->getMock();
        $this->voter = new ProjectParticipationMemberVoter(
            $authorizationChecker,
            $projectParticipationManager
        );
    }

    /**
     * @throws \Exception
     */
    public function testVoteOnRightSubject()
    {
        $staff = $this->participation->getParticipant()->getStaff()[0];
        $participationMember = new ProjectParticipationMember(
            $this->participation,
            $staff,
            $staff
        );
        $this->expectAccessAbstained('hello world', $staff->getClient(), $participationMember);
        $this->expectAccessAbstained(
            ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA,
            $staff->getClient(),
            $staff
        );
    }

    /**
     * A user that is not the staff of a participation should not be able to sign NDA
     *
     * @throws \Exception
     */
    public function testExternalUserCannotAcceptNda()
    {
        $externalClient = $this->getFakeClient();
        $staff = $this->participation->getParticipant()->getStaff()[0];
        $participationMember = new ProjectParticipationMember(
            $this->participation,
            $staff,
            $staff
        );
        $this->expectAccessDenied(
            ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA,
            $externalClient,
            $participationMember
        );
    }

    /**
     * A participationMember staff can accept the NDA
     *
     * @throws \Exception
     */
    public function testCanAcceptNda()
    {
        $staff = $this->participation->getParticipant()->getStaff()[0];
        $participationMember = new ProjectParticipationMember(
            $this->participation,
            $staff,
            $staff
        );
        $this->expectAccessGranted(
            ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA,
            $staff->getClient(),
            $participationMember
        );
    }

    /**
     * Generates a new without relation for the current participation
     *
     * @return Clients
     *
     * @throws \Exception
     */
    private function getFakeClient(): Clients
    {
        $client = new Clients('email2@demo.fr');
        $company = new Company('B', 'b');
        $fakeStaff = $this->getMockBuilder(Staff::class)
            ->disableOriginalConstructor()
            ->getMock();
        $staff = new Staff($company, $client, $fakeStaff);
        $client->setCurrentStaff($staff);

        return $client;
    }
}
