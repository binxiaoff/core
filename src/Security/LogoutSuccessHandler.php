<?php

namespace Unilend\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    /**
     * @inheritDoc
     */
    public function onLogoutSuccess(Request $request)
    {
        $response = parent::onLogoutSuccess($request);
        $response->headers->clearCookie(LoginAuthenticator::COOKIE_NO_CF);

        return $response;
    }
}
