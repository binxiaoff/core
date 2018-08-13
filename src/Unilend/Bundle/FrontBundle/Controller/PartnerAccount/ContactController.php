<?php

namespace Unilend\Bundle\FrontBundle\Controller\PartnerAccount;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\FrontBundle\Form\PartnerContactType;

class ContactController extends Controller
{
    /**
     * @Route("partenaire/contact", name="partner_contact")
     * @Security("has_role('ROLE_PARTNER')")
     *
     * @param Request                    $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function contactAction(Request $request, ?UserInterface $client)
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
                $filePath           = '';
                $file               = $request->files->get('attachment');
                $settingsRepository = $this->get('doctrine.orm.entity_manager')->getRepository('UnilendCoreBusinessBundle:Settings');
                $keywords           = [
                    '[staticUrl]' => $this->get('assets.packages')->getUrl(''),
                    '[partner]'   => $this->get('unilend.service.partner_manager')->getPartner($client)->getIdCompany()->getName(),
                    '[firstname]' => $client->getPrenom(),
                    '[lastname]'  => $client->getNom(),
                    '[email]'     => $formData['email'],
                    '[phone]'     => $formData['phone'],
                    '[message]'   => $formData['message']
                ];

                /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
                $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact-partenaire', $keywords, false);

                try {
                    $message->setTo($settingsRepository->findOneBy(['type' => 'Adresse emprunteur'])->getValue());

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
                } catch (\Exception $exception) {
                    $this->get('logger')->error(
                        'Could not send email : notification-demande-de-contact-partenaire - Exception: ' . $exception->getMessage(),
                        ['id_mail_template' => $message->getTemplateId(), 'email address' => $formData['email'], 'email_details' => $keywords, 'class' => __CLASS__, 'function' => __FUNCTION__]
                    );
                    $this->addFlash('error', $translator->trans('common-validator_email-address-invalid'));
                }
            }
        }

        return $this->render('/partner_account/contact.html.twig', ['contact_form' => $contactForm->createView()]);
    }
}
