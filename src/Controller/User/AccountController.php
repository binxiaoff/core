<?php

declare(strict_types=1);

namespace Unilend\Controller\User;

use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\TemporaryLinksLogin;
use Unilend\Form\User\InitProfileType;
use Unilend\Message\Client\ClientCreated;
use Unilend\Repository\ClientsRepository;
use Unilend\Repository\TemporaryLinksLoginRepository;

class AccountController extends AbstractController
{
    /**
     * @Route("/compte/initialisation/{securityToken}", name="account_init", requirements={"securityToken": "[0-9a-f]+"}, methods={"GET", "POST"})
     *
     * @ParamConverter("temporaryLink", options={"mapping": {"securityToken": "token"}})
     *
     * @param Request                       $request
     * @param TemporaryLinksLogin           $temporaryLink
     * @param TemporaryLinksLoginRepository $temporaryLinksLoginRepository
     * @param TranslatorInterface           $translator
     * @param UserPasswordEncoderInterface  $userPasswordEncoder
     * @param ClientsRepository             $clientsRepository
     * @param MessageBusInterface           $messageBus
     *
     * @throws Exception
     *
     * @return Response
     */
    public function init(
        Request $request,
        TemporaryLinksLogin $temporaryLink,
        TemporaryLinksLoginRepository $temporaryLinksLoginRepository,
        TranslatorInterface $translator,
        UserPasswordEncoderInterface $userPasswordEncoder,
        ClientsRepository $clientsRepository,
        MessageBusInterface $messageBus
    ): Response {
        if ($temporaryLink->getExpires() < new DateTime()) {
            $this->addFlash('error', $translator->trans('account-init.invalid-link-error-message'));

            return $this->render('user/init.html.twig');
        }

        $client = $temporaryLink->getIdClient();

        if (null === $client || false === $client->isValidated() || false === empty($client->getPassword())) {
            return $this->redirectToRoute('home');
        }

        $temporaryLink->setAccessed(new DateTime());
        $temporaryLinksLoginRepository->save($temporaryLink);

        $form = $this->createForm(InitProfileType::class);
        $form->get('identity')->setData($client);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $encryptedPassword = $userPasswordEncoder->encodePassword($client, $formData['password']['plainPassword']);
            $client
                ->setFirstName($formData['identity']->getFirstName())
                ->setLastName($formData['identity']->getLastName())
                ->setJobFunction($formData['identity']->getJobFunction())
                ->setMobile($formData['identity']->getMobile())
                ->setPhone($formData['identity']->getPhone())
                ->setPassword($encryptedPassword)
                ->setSecurityQuestion($formData['securityQuestion']['securityQuestion'])
                ->setSecurityAnswer($formData['securityQuestion']['securityAnswer'])
            ;

            $clientsRepository->save($client);

            $temporaryLink->setExpires(new DateTime());
            $temporaryLinksLoginRepository->save($temporaryLink);

            $messageBus->dispatch(new ClientCreated($client->getIdClient()));

            return $this->redirectToRoute('login');
        }

        return $this->render('user/init.html.twig', ['form' => $form->createView()]);
    }
}
