<?php

declare(strict_types=1);

namespace Unilend\Core\Event\TemporaryToken;

final class TemporaryTokenAuthenticationEvents
{
    public const AUTHENTICATION_SUCCESS = 'unilend.temporary_token.on_authentication_success';

    public const AUTHENTICATION_FAILURE = 'unilend.temporary_token.on_authentication_failure';
}
