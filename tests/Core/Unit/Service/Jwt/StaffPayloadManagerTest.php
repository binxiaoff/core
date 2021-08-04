<?php

declare(strict_types=1);

namespace Unilend\Test\Core\Unit\Service\Jwt;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Unilend\Core\Entity\Company;
use Unilend\Core\Entity\Staff;
use Unilend\Core\Entity\Team;
use Unilend\Core\Entity\User;
use Unilend\Core\Service\Jwt\StaffPayloadManager;
use Unilend\Core\Service\Staff\StaffLoginChecker;

/**
 * @coversDefaultClass \Unilend\Core\Service\Jwt\StaffPayloadManager
 *
 * @internal
 */
class StaffPayloadManagerTest extends TestCase
{
    /** @var IriConverterInterface|ObjectProphecy */
    private $iriConverter;

    /** @var StaffLoginChecker|ObjectProphecy */
    private $staffLoginChecker;

    protected function setUp(): void
    {
        $this->iriConverter      = $this->prophesize(IriConverterInterface::class);
        $this->staffLoginChecker = $this->prophesize(StaffLoginChecker::class);
    }

    protected function tearDown(): void
    {
        $this->iriConverter      = null;
        $this->staffLoginChecker = null;
    }

    /**
     * @covers ::getScope
     */
    public function testGetScope(): void
    {
        static::assertSame('staff', StaffPayloadManager::getScope());
    }

    /**
     * @covers ::getPayloads
     */
    public function testGetPayloads(): void
    {
        $user = new User('user@mail.com');
        // staff 1
        $staff1 = $this->createStaff($user);
        $staff1->setPublicId();
        $staff1Iri = '/core/staff/' . $staff1->getPublicId();
        // staff 2
        $staff2 = $this->createStaff($user);
        $this->forceStaff($user, new ArrayCollection([$staff1, $staff2]));
        // staff 3
        $staff3 = $this->createStaff($user);
        $staff3->setPublicId();
        $staff3Iri = '/core/staff/' . $staff3->getPublicId();
        $this->forceStaff($user, new ArrayCollection([$staff1, $staff2, $staff3]));

        $this->staffLoginChecker->isGrantedLogin($staff1)->shouldBeCalledOnce()->willReturn(true);
        $this->iriConverter->getIriFromItem($staff1)->shouldBeCalledOnce()->willReturn($staff1Iri);
        $this->staffLoginChecker->isGrantedLogin($staff2)->shouldBeCalledOnce()->willReturn(false);
        $this->iriConverter->getIriFromItem($staff2)->shouldNotBeCalled();
        $this->staffLoginChecker->isGrantedLogin($staff3)->shouldBeCalledOnce()->willReturn(true);
        $this->iriConverter->getIriFromItem($staff3)->shouldBeCalledOnce()->willReturn($staff3Iri);

        $staffPayloadManager = $this->createTestObject();
        $result              = \iterator_to_array($staffPayloadManager->getPayloads($user));

        static::assertCount(2, $result);

        foreach (\range(0, 1) as $index) {
            static::assertArrayHasKey('staff', $result[$index]);
        }
    }

    /**
     * @covers ::updateSecurityToken
     */
    public function testUpdateSecurityToken(): void
    {
        $staff = $this->createStaff();
        $staff->setPublicId();
        $staffIri = 'core/staff/' . $staff->getPublicId();
        $token    = $this->createToken($staff);
        $payload  = ['staff' => $staffIri];

        $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []])->shouldBeCalledOnce()->willReturn($staff);

        $staffPayloadManager = $this->createTestObject();
        $staffPayloadManager->updateSecurityToken($token, $payload);
        $tokenAttributes = $token->getAttributes();

        static::assertArrayHasKey('staff', $tokenAttributes);
        static::assertArrayHasKey('company', $tokenAttributes);
        static::assertSame($staff, $tokenAttributes['staff']);
        static::assertSame($staff->getCompany(), $tokenAttributes['company']);
    }

    /**
     * @covers ::updateSecurityToken
     */
    public function testUpdateSecurityTokenWithoutStaffInPayload(): void
    {
        $staff   = $this->createStaff();
        $token   = $this->createToken($staff);
        $payload = [];

        $this->iriConverter->getItemFromIri(Argument::cetera())->shouldNotBeCalled();

        $staffPayloadManager = $this->createTestObject();
        $staffPayloadManager->updateSecurityToken($token, $payload);
        $tokenAttributes = $token->getAttributes();

        static::assertArrayNotHasKey('staff', $tokenAttributes);
        static::assertArrayNotHasKey('company', $tokenAttributes);
    }

    /**
     * @covers ::isPayloadValid
     */
    public function testIsPayloadValidWithStaff(): void
    {
        $staff = $this->createStaff();
        $staff->setPublicId();
        $staffIri = 'core/staff/' . $staff->getPublicId();
        $payload  = ['staff' => $staffIri];

        $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []])->shouldBeCalledOnce()->willReturn($staff);
        $this->staffLoginChecker->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(true);

        $staffPayloadManager = $this->createTestObject();
        static::assertTrue($staffPayloadManager->isPayloadValid($payload));
    }

    /**
     * @covers ::isPayloadValid
     */
    public function testIsPayloadValidWithoutStaff(): void
    {
        $staff   = $this->createStaff();
        $payload = [];

        $this->iriConverter->getItemFromIri(Argument::cetera())->shouldNotBeCalled();
        $this->staffLoginChecker->isGrantedLogin($staff)->shouldNotBeCalled();

        $staffPayloadManager = $this->createTestObject();
        static::assertTrue($staffPayloadManager->isPayloadValid($payload));
    }

    /**
     * @covers ::isPayloadValid
     */
    public function testIsPayloadInvalidWithStaffNotFound(): void
    {
        $staffIri = 'core/staff/42';
        $payload  = ['staff' => $staffIri];

        $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []])->shouldBeCalledOnce()->willThrow(ItemNotFoundException::class);
        $this->staffLoginChecker->isGrantedLogin(Argument::any())->shouldNotBeCalled();

        $staffPayloadManager = $this->createTestObject();
        static::assertFalse($staffPayloadManager->isPayloadValid($payload));
    }

    /**
     * @covers ::isPayloadValid
     */
    public function testIsPayloadInvalidWithStaffNotGrantedLogin(): void
    {
        $staff = $this->createStaff();
        $staff->setPublicId();
        $staffIri = 'core/staff/' . $staff->getPublicId();
        $payload  = ['staff' => $staffIri];

        $this->iriConverter->getItemFromIri($staffIri, [AbstractNormalizer::GROUPS => []])->shouldBeCalledOnce()->willReturn($staff);
        $this->staffLoginChecker->isGrantedLogin($staff)->shouldBeCalledOnce()->willReturn(false);

        $staffPayloadManager = $this->createTestObject();
        static::assertFalse($staffPayloadManager->isPayloadValid($payload));
    }

    private function createToken(Staff $staff): TokenInterface
    {
        $user = $staff->getUser();
        $user->setCurrentStaff($staff);

        return new JWTUserToken($user->getRoles(), $user);
    }

    private function createStaff(?User $user = null): Staff
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);

        return new Staff($user ?? new User('user@mail.com'), $team);
    }

    private function forceStaff(User $user, ArrayCollection $staff): void
    {
        $reflection = new \ReflectionClass(User::class);
        $property   = $reflection->getProperty('staff');
        $property->setAccessible(true);
        $property->setValue($user, $staff);
    }

    private function createTestObject(): StaffPayloadManager
    {
        return new StaffPayloadManager(
            $this->iriConverter->reveal(),
            $this->staffLoginChecker->reveal()
        );
    }
}
