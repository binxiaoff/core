<?php

declare(strict_types=1);

namespace KLS\Core\Service;

use Rollbar\Rollbar;
use Rollbar\Symfony\RollbarBundle\Factories\RollbarHandlerFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RollbarPersonHandler extends RollbarHandlerFactory
{
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        Rollbar::logger()->configure([
            'person_fn' => function () use ($container) {
                $token = $container->get('security.token_storage')->getToken();

                if ($token) {
                    $user = $token->getUser();

                    /*
                     * email formatted
                     * e.g (jean.dupond@kls-platform.com =>  j***.d*****@kls-platform.com)
                     */
                    return [
                        'id'    => $user->getId(),
                        'email' => \preg_replace('/(?<=\w)\w(?=.*@)/', '*', $user->getEmail()),
                    ];
                }

                return [];
            },
        ]);
    }
}
