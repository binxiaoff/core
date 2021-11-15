<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Arrangement\Unit\Service\Jwt;

use Exception;
use KLS\Core\Entity\Company;
use KLS\Core\Entity\Staff;
use KLS\Core\Entity\User;
use KLS\Syndication\Arrangement\Service\Jwt\PermissionProvider;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \KLS\Syndication\Arrangement\Service\Jwt\PermissionProvider
 *
 * @internal
 */
class PermissionProviderTest extends TestCase
{
    /**
     * @covers ::getProductName
     */
    public function testGetProductName(): void
    {
        $permissionProvider = new PermissionProvider();

        static::assertSame('syndication', $permissionProvider->getProductName());
    }

    /**
     * @covers ::getServiceName
     */
    public function testGetServiceName(): void
    {
        $permissionProvider = new PermissionProvider();

        static::assertSame('arrangement', $permissionProvider->getServiceName());
    }

    /**
     * @throws Exception
     */
    public function providerPermissions()
    {
        $user = new User('test@test.com');

        $staff = new Staff($user, (new Company('test', '514919844'))->getRootTeam());

        foreach (['no user given' => null, 'a user given' => $user] as $userTest => $userValue) {
            foreach (['no staff given' => null, 'a staff given' => $staff] as $staffTest => $staffValue) {
                yield 'It should return the correct value with ' . $userTest . ' and ' . $staffTest => [
                    $userValue,
                    $staffValue,
                ];
            }
        }
    }

    /**
     * @covers ::getPermissions
     *
     * @dataProvider providerPermissions
     */
    public function testGetPermissions(?User $user, ?Staff $staff): void
    {
        $permissionProvider = new PermissionProvider();

        static::assertSame(1, $permissionProvider->getPermissions());
    }

    /**
     * @covers ::getGrantPermissions
     *
     * @dataProvider providerPermissions
     */
    public function testGetGrantPermissions(?User $user, ?Staff $staff): void
    {
        $permissionProvider = new PermissionProvider();

        static::assertSame(0, $permissionProvider->getGrantPermissions());
    }
}
