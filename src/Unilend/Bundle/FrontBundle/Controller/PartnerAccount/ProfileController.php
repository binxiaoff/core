<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\FrontBundle\Form\ClientPasswordType;
use Unilend\core\Loader;

class ProfileController extends Controller
{
    /**
     * @Route("/partenaire/profil", name="partner_profile")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function profileAction(Request $request)
    {
        $client = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($this->getUser()->getClientId());
        $form   = $this->createForm(ClientPasswordType::class);

        if ($request->isMethod(Request::METHOD_POST)) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $this->handlePasswordForm($client, $form);
                return $this->redirectToRoute('partner_profile');
            }
        }

        return $this->render('/partner_account/profile.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @param Clients       $client
     * @param FormInterface $form
     *
     * @return bool
     */
    public function handlePasswordForm(Clients $client, FormInterface $form)
    {
        $translator              = $this->get('translator');
        $securityPasswordEncoder = $this->get('security.password_encoder');
        $entityManager           = $this->get('doctrine.orm.entity_manager');

        /** @var \ficelle $ficelle */
        $ficelle = Loader::loadLib('ficelle');

        if (false === $securityPasswordEncoder->isPasswordValid($this->getUser(), $form->get('formerPassword')->getData())) {
            $form->get('formerPassword')->addError(new FormError($translator->trans('lender-profile_security-password-section-error-wrong-former-password')));
            $this->addFlash('error', $translator->trans('partner-profile_security-password-section-error-wrong-former-password'));
        }

        if (false === $ficelle->password_fo($form->get('password')->getData(), 6)) {
            $form->get('password')->addError(new FormError($translator->trans('common-validator_password-invalid')));
            $this->addFlash('error', $translator->trans('common-validator_password-invalid'));
        }

        if ($form->isValid()) {
            $client->setPassword($securityPasswordEncoder->encodePassword($this->getUser(), $form->get('password')->getData()));
            $entityManager->flush($client);

            $this->addFlash('success', $translator->trans('partner-profile_security-password-section-form-success-message'));

            return true;
        }

        return false;
    }
}
