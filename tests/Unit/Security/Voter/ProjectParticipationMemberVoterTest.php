<?php

namespace Unilend\Test\Unit\Security\Voter;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Unilend\Core\Entity\{User, Company, Embeddable\Money, MarketSegment, Staff};
use Unilend\Syndication\Entity\{Project, ProjectParticipation, ProjectParticipationMember};
use Unilend\Syndication\Security\Voter\ProjectParticipationMemberVoter;
use Unilend\Syndication\Service\ProjectParticipation\ProjectParticipationManager;

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
        $user = new User('email@demo.fr');
        $staff = new Staff($company, $user, $arrangerStaff);
        $user->setCurrentStaff($staff);
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
        $this->expectAccessAbstained('hello world', $staff->getUser(), $participationMember);
        $this->expectAccessAbstained(
            ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA,
            $staff->getUser(),
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
        $externalUser = $this->getFakeUser();
        $arrangerStaff = $this->getMockBuilder(Staff::class)
            ->disableOriginalConstructor()
            ->getMock();
        $staff = new Staff($this->participation->getParticipant(), $externalUser, $arrangerStaff);
        $participationMember = new ProjectParticipationMember(
            $this->participation,
            $staff,
            $staff
        );
        $this->expectAccessDenied(
            ProjectParticipationMemberVoter::ATTRIBUTE_ACCEPT_NDA,
            $externalUser,
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
            $staff->getUser(),
            $participationMember
        );
    }

    /**
     * Generates a new without relation for the current participation
     *
     * @return User
     *
     * @throws \Exception
     */
    private function getFakeUser(): User
    {
        $user = new User('email2@demo.fr');
        $company = new Company('B', 'b');
        $fakeStaff = $this->getMockBuilder(Staff::class)
            ->disableOriginalConstructor()
            ->getMock();
        $staff = new Staff($company, $user, $fakeStaff);
        $user->setCurrentStaff($staff);

        return $user;
    }
}
