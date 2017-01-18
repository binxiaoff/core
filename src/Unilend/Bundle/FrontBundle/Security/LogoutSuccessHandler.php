<?php

namespace Unilend\Bundle\FrontBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class LogoutSuccessHandler extends DefaultLogoutSuccessHandler
{
    public function onLogoutSuccess(Request $request) {
        $response = parent::onLogoutSuccess($request);
        $response->headers->clearCookie(LoginAuthenticator::COOKIE_NO_CF);

        return $response;
    }
}
