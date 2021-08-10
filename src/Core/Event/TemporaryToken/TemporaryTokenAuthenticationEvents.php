<?php

declare(strict_types=1);

namespace KLS\Core\Event\TemporaryToken;

final class TemporaryTokenAuthenticationEvents
{
    public const AUTHENTICATION_SUCCESS = 'kls.temporary_token.on_authentication_success';

    public const AUTHENTICATION_FAILURE = 'kls.temporary_token.on_authentication_failure';
}
