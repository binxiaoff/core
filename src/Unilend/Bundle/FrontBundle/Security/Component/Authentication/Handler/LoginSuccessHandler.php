<?php


namespace Unilend\Bundle\FrontBundle\Security\Component\Authentication\Handler;


use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $router;


    public function __construct(Router $router)
    {
        $this->router = $router;
    }


    /**
     * @inheritDoc
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        $roles  = $token->getUser()->getRoles();

        if (in_array('ROLE_LENDER', $roles)){
            return new RedirectResponse($this->router->generate('lender_dashboard'));
        }

        if (in_array('ROLE_BORROWER', $roles)){
            return new RedirectResponse($this->router->generate('/espace-emprunteur/projets'));
        }

        return null;
    }

}
