<?php

declare(strict_types=1);

namespace KLS\Test\Core\Unit\Service\Jwt;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Exception\ItemNotFoundException;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\Team;
use KLS\Core\Entity\User;
use KLS\Core\Service\Jwt\StaffPayloadManager;
use KLS\Core\Service\Staff\StaffLoginChecker;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Authentication\Token\JWTUserToken;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @coversDefaultClass \KLS\Core\Service\Jwt\StaffPayloadManager
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
        $user           = $this->createUserWithStaff(3);
        $staff1         = $user->getStaff()[0];
        $staff2         = $user->getStaff()[1];
        $staff3         = $user->getStaff()[2];
        $staffIriPrefix = '/core/staff/';

        $this->staffLoginChecker->isGrantedLogin($staff1)->shouldBeCalledOnce()->willReturn(true);
        $this->iriConverter->getIriFromItem($staff1)->shouldBeCalledOnce()->willReturn($staffIriPrefix . $staff1->getPublicId());
        $this->staffLoginChecker->isGrantedLogin($staff2)->shouldBeCalledOnce()->willReturn(false);
        $this->iriConverter->getIriFromItem($staff2)->shouldNotBeCalled();
        $this->staffLoginChecker->isGrantedLogin($staff3)->shouldBeCalledOnce()->willReturn(true);
        $this->iriConverter->getIriFromItem($staff3)->shouldBeCalledOnce()->willReturn($staffIriPrefix . $staff3->getPublicId());

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
        $staff    = $this->createUserWithStaff()->getCurrentStaff();
        $staffIri = 'core/staff/' . $staff->getPublicId();
        $payload  = ['staff' => $staffIri];
        $token    = $this->createToken($staff);

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
        $staff   = $this->createUserWithStaff()->getCurrentStaff();
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
        $staff    = $this->createUserWithStaff()->getCurrentStaff();
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
        $staff   = $this->createUserWithStaff()->getCurrentStaff();
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
        $staff = $this->createUserWithStaff()->getCurrentStaff();
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

    /**
     * @throws Exception
     */
    private function createUserWithStaff(int $staffNb = 1): User
    {
        $teamRoot = Team::createRootTeam(new Company('Company', 'Company', ''));
        $team     = Team::createTeam('Team', $teamRoot);
        $user     = new User('user@mail.com');
        $staff    = new ArrayCollection();

        foreach (\range(1, $staffNb) as $index) {
            $staffItem = new Staff($user, $team);
            $staffItem->setPublicId();
            $staff->add($staffItem);
        }

        $user->setCurrentStaff($staff[0]);

        // force User::setStaff
        $reflection = new \ReflectionClass(User::class);
        $property   = $reflection->getProperty('staff');
        $property->setAccessible(true);
        $property->setValue($user, $staff);

        return $user;
    }

    private function createTestObject(): StaffPayloadManager
    {
        return new StaffPayloadManager(
            $this->iriConverter->reveal(),
            $this->staffLoginChecker->reveal()
        );
    }
}
