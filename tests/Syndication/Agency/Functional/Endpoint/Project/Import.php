<?php

declare(strict_types=1);

namespace KLS\Test\Syndication\Agency\Functional\Endpoint\Project;

use AsyncAws\Core\Test\Http\SimpleMockedResponse;
use KLS\Core\Repository\StaffRepository;
use KLS\Test\Core\Functional\Api\AbstractApiTest;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @internal
 *
 * @coversNothing
 */
class Import extends AbstractApiTest
{
    private const ENDPOINT = '/agency/projects?import=%s';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function provider()
    {
        yield 'It works with project where user is member of the arranger entity of finished project' => [
            'staff_company:basic_user-1',
            'project/finished',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_CREATED
            ),
        ];

        yield 'It works with project where user is member of the agent entity of finished project' => [
            'staff_company:basic_user-9',
            'project/finished',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_CREATED
            ),
        ];

        yield 'It does\'nt work with project where user is member of the arranger entity of not finished project' => [
            'staff_company:basic_user-9',
            'project/basic_arranger',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_FORBIDDEN
            ),
        ];

        yield 'It does\'nt work with project where user is member of the agent entity of not finished project' => [
            'staff_company:basic_user-9',
            'project/basic_arranger',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_FORBIDDEN
            ),
        ];

        yield 'It does\'nt work with project where user can see finished project but is not arranger of agent' => [
            'staff_company:foo_user-a',
            'project/finished',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_FORBIDDEN
            ),
        ];

        yield 'It does\'nt work with project where user cannot see finished project' => [
            'staff_company:foo_user-10',
            'project/finished',
            new SimpleMockedResponse(
                '',
                [],
                Response::HTTP_FORBIDDEN
            ),
        ];
    }

    /**
     * @dataProvider ::provider
     *
     * @throws TransportExceptionInterface
     */
    public function test(string $staffPublicId, string $arrangementPublicId, ResponseInterface $expected)
    {
        $staff = static::$kernel
            ->getContainer()
            ->get(StaffRepository::class)
            ->findOneBy(['publicId' => $staffPublicId])
        ;

        $client = $this->createAuthClient($staff);

        $response = $client->request('POST', \sprintf(static::ENDPOINT, $arrangementPublicId));

        static::assertSame($expected->getStatusCode(), $response->getStatusCode());
        // TODO Handle content and header later
        //static::assertSame($expected->getContent(), $response->getContent());
        //static::assertSame($expected->getHeaders(), $response->getHeaders());
    }
}
