<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Unilend\Bundle\FrontBundle\Form\PartnerContactType;
use Unilend\Bundle\FrontBundle\Security\User\UserPartner;

class ContactController extends Controller
{
    /**
     * @Route("partenaire/contact", name="partner_contact")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function contactAction(Request $request)
    {
        $contactForm = $this->createForm(PartnerContactType::class);
        $contactForm->handleRequest($request);

        if ($contactForm->isSubmitted() && $contactForm->isValid()) {
            $error      = false;
            $formData   = $contactForm->getData();
            $translator = $this->get('translator');

            if (empty($formData['phone']) || strlen($formData['phone']) < 9 || strlen($formData['phone']) > 14) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_phone-number-invalid'));
            }
            if (empty($formData['email']) || false == filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_email-address-invalid'));
            }
            if (empty($formData['message'])) {
                $error = true;
                $this->addFlash('error', $translator->trans('common-validator_email-message-empty'));
            }

            if (false === $error) {
                /** @var UserPartner $user */
                $user               = $this->getUser();
                $filePath           = '';
                $file               = $request->files->get('attachment');
                $client             = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Clients')->find($user->getClientId());
                $settingsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Settings');
                $keywords           = [
                    '[staticUrl]' => $this->get('assets.packages')->getUrl(''),
                    '[partner]'   => $user->getPartner()->getIdCompany()->getName(),
                    '[firstname]' => $client->getPrenom(),
                    '[lastname]'  => $client->getNom(),
                    '[email]'     => $formData['email'],
                    '[phone]'     => $formData['phone'],
                    '[message]'   => $formData['message']
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact-partenaire', $keywords, false);
                $message->setTo(trim($settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue()));

                if ($file instanceof UploadedFile) {
                    $uploadDestination = $this->getParameter('path.protected') . 'contact/';
                    $file              = $file->move($uploadDestination, $file->getClientOriginalName());
                    $filePath          = $file->getPathname();
                    $message->attach(\Swift_Attachment::fromPath($filePath));
                }

                $mailer = $this->get('mailer');
                $mailer->send($message);

                if (false === empty($filePath)) {
                    @unlink($filePath);
                }

                $this->addFlash('success', $translator->trans('partner-contact_success-message'));

                return $this->redirectToRoute('partner_contact');
            }
        }

        return $this->render('/partner_account/contact.html.twig', ['contact_form' => $contactForm->createView()]);
    }
}
