<?php

declare(strict_types=1);

namespace KLS\Test\Core\Functional\Api;

use ApiPlatform\Core\Api\IriConverterInterface;
use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use KLS\Core\EventSubscriber\Jwt\VersionSubscriber;
use KLS\Core\Repository\UserRepository;
use KLS\Test\Core\DataFixtures\UserFixtures;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversNothing
 *
 * @internal
 */
class AuthenticationTokenTest extends ApiTestCase
{
    private const ENDPOINT_AUTH_TOKEN = '/core/authentication_token';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function successfullProvider(): iterable
    {
        foreach (\range(1, 10) as $index) {
            yield 'user-' . $index => ['user-' . $index];
        }
        foreach (\range(21, 25) as $index) {
            yield 'user-' . $index => ['user-' . $index];
        }
        foreach (\range('a', 'z') as $index) {
            yield 'user-' . $index => ['user-' . $index];
        }
    }

    /**
     * @dataProvider successfullProvider
     */
    public function testAuthenticationToken(string $userPublicId): void
    {
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => $userPublicId]);

        $response = static::createClient()->request(
            Request::METHOD_POST,
            self::ENDPOINT_AUTH_TOKEN,
            [
                'json' => [
                    'username' => $user->getEmail(),
                    'password' => UserFixtures::DEFAULT_PASSWORD,
                ],
            ]
        );
        $response = $response->toArray();

        $decoder       = static::$container->get(JWTEncoderInterface::class);
        $iriConverter  = static::$container->get(IriConverterInterface::class);
        $decodedTokens = \array_map([$decoder, 'decode'], $response['tokens']);

        static::assertArrayNotHasKey('token', $response);
        static::assertArrayHasKey('refresh_token', $response);
        static::assertArrayHasKey('tokens', $response);
        static::assertNotEmpty($response['tokens']);

        foreach ($decodedTokens as $decodedToken) {
            static::assertSame(VersionSubscriber::JWT_VERSION, $decodedToken['version']);
            static::assertSame($iriConverter->getIriFromItem($user), $decodedToken['user']);
            static::assertArrayHasKey('credit_guaranty', $decodedToken['permissions']);
            static::assertArrayHasKey('syndication', $decodedToken['permissions']);

            if (\in_array('staff', \array_values($decodedToken))) {
                static::assertSame('staff', $decodedToken['@scope']);
                static::assertArrayHasKey('staff', $decodedToken);
            } else {
                static::assertArrayNotHasKey('staff', $decodedToken);
            }
        }
    }

    public function unauthorizedProvider(): iterable
    {
        yield 'user-uninitialized' => ['user-uninitialized', UserFixtures::DEFAULT_PASSWORD];
        yield 'user-1 with wrong password' => ['user-1', 'test'];

        foreach (\range(15, 20) as $index) {
            yield 'user-' . $index => ['user-' . $index, UserFixtures::DEFAULT_PASSWORD];
        }
    }

    /**
     * @dataProvider unauthorizedProvider
     */
    public function testAuthenticationTokenUnauthorized(string $userPublicId, string $password): void
    {
        $user = static::$container->get(UserRepository::class)->findOneBy(['publicId' => $userPublicId]);

        $response = static::createClient()->request(
            Request::METHOD_POST,
            self::ENDPOINT_AUTH_TOKEN,
            [
                'json' => [
                    'username' => $user->getEmail(),
                    'password' => $password,
                ],
            ]
        );

        static::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
