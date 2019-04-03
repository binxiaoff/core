<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\FrontBundle\Service\ProjectDisplayManager;
use Unilend\core\Loader;
use Unilend\Entity\{Clients, Companies, Projects, Settings};

class ContactController extends Controller
{
    const CONTACT_ROLE_PRESS       = 1;
    const CONTACT_ROLE_LENDER      = 2;
    const CONTACT_ROLE_BORROWER    = 3;
    const CONTACT_ROLE_RECRUITMENT = 4;
    const CONTACT_ROLE_OTHER       = 5;
    const CONTACT_ROLE_PARTNER     = 6;

    /**
     * @Route("/contact", name="contact")
     *
     * @param Request                     $request
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function contactAction(Request $request, ?UserInterface $client): Response
    {
        $template = [];

        if ($client instanceof Clients) {
            $template['formData'] = $this->getContactFormTemplateData($client);
        }

        if ($request->request->has('message')) {
            $post = $request->request->get('message');
            $this->contactForm($post);
            if ($this->get('session')->getFlashBag()->has('contactErrors')) {
                $template['formData'] = $post;
            }
        }

        return $this->render('contact/contact.html.twig', $template);
    }

    /**
     * @Route("/contact/search/{query}", name="contact_search_result")
     *
     * @param Request                     $request
     * @param string                      $query
     * @param UserInterface|Clients|null $client
     *
     * @return Response
     */
    public function contactSearchResultAction(Request $request, string $query, ?UserInterface $client): Response
    {
        $template['query'] = $query;

        if (Request::METHOD_GET === $request->getMethod()) {
            if (null !== $query) {
                $template['results'] = $this->getSearchResult($query, $client);
            }
        }

        if ($client instanceof Clients) {
            $template['formData'] = $this->getContactFormTemplateData($client);
        }

        return $this->render('contact/contact.html.twig', $template);
    }

    /**
     * @param Clients $client
     *
     * @return array
     */
    private function getContactFormTemplateData(Clients $client): array
    {
        if (false === $client->isLender() && false === $client->isBorrower() && false === $client->isPartner()) {
            return [];
        }

        if (false === $client->isNaturalPerson() || $client->isBorrower() || $client->isPartner()) {
            $company = $this->get('doctrine.orm.entity_manager')->getRepository(Companies::class)->findOneBy(['idClientOwner' => $client]);
        }

        $formData = [
            'firstname' => $client->getPrenom(),
            'lastname'  => $client->getNom(),
            'phone'     => $client->getMobile(),
            'email'     => $client->getEmail(),
            'company'   => empty($company) ? '' : $company->getName(),
            'role'      => $client->isLender() ? self::CONTACT_ROLE_LENDER : ($client->isBorrower() ? self::CONTACT_ROLE_BORROWER : ($client->isPartner() ? self::CONTACT_ROLE_PARTNER : ''))
        ];

        return $formData;
    }

    /**
     * @Route("/contact/search", name="contact_search", methods={"POST"})
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request): Response
    {
        return $this->redirectToRoute('contact_search_result', ['query' => urlencode($request->request->get('search'))]);
    }

    /**
     * @param  string       $query
     * @param  Clients|null $client
     *
     * @return array
     */
    private function getSearchResult(string $query, ?Clients $client): array
    {
        $query   = filter_var(urldecode($query), FILTER_SANITIZE_STRING);
        $search  = $this->get('unilend.service.search_service');
        $results = $search->search($query);

        if (false === empty($results['projects'])) {
            if (false === $client instanceof Clients) {
                unset($results['projects']);
            } else {
                $projectDisplayManager = $this->get('unilend.frontbundle.service.project_display_manager');
                $projectRepository     = $this->get('doctrine.orm.entity_manager')->getRepository(Projects::class);

                foreach ($results['projects'] as $index => $result) {
                    $project = $projectRepository->find($result['projectId']);

                    if (ProjectDisplayManager::VISIBILITY_FULL !== $projectDisplayManager->getVisibility($project, $client)) {
                        unset($results['projects'][$index]);
                    }
                }

                if (empty($results['projects'])) {
                    unset($results['projects']);
                }
            }
        }

        return $results;
    }

    /**
     * @param array $post
     */
    private function contactForm(array $post): void
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->get('translator');

        if (empty($post['phone']) || false === is_numeric($post['phone'])) {
            $this->addFlash('contactErrors', $translator->trans('common-validator_phone-number-invalid'));
        }

        if (empty($post['role']) || false === filter_var($post['role'], FILTER_VALIDATE_INT)) {
            $this->addFlash('contactErrors', $translator->trans('contact_contact-form-subject-error-message'));
        }

        if (empty($post['lastname'])) {
            $this->addFlash('contactErrors', $translator->trans('common-validator_last-name-empty'));
        }

        if (empty($post['firstname'])) {
            $this->addFlash('contactErrors', $translator->trans('common-validator_first-name-empty'));
        }

        if (empty($post['email']) || false === filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('contactErrors', $translator->trans('common-validator_email-address-invalid'));
        }

        if (empty($post['body'])) {
            $this->addFlash('contactErrors', $translator->trans('contact_contact-form-missing-message-error-message'));
        }

        if (false === $this->get('session')->getFlashBag()->has('contactErrors')) {
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            $objets = ['', 'Relation presse', 'Demande preteur', 'Demande Emprunteur', 'Recrutement', 'Autre', 'Partenariat'];

            $keywords = [
                'firstName' => $post['firstname']
            ];

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $keywords);

            try {
                $message->setTo($post['email']);
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->warning(
                    'Could not send email : demande-de-contact - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'email address' => $post['email'], 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
                $this->addFlash('contactErrors', $translator->trans('common-validator_email-address-invalid'));

                return;
            }

            switch ($post['role']) {
                case self::CONTACT_ROLE_PRESS:
                    $settingType = 'Adresse presse';
                    break;
                case self::CONTACT_ROLE_LENDER:
                    $settingType = 'Adresse preteur';
                    break;
                case self::CONTACT_ROLE_BORROWER:
                    $settingType = 'Adresse emprunteur';
                    break;
                case self::CONTACT_ROLE_RECRUITMENT:
                    $settingType = 'Adresse recrutement';
                    break;
                case self::CONTACT_ROLE_PARTNER:
                    $settingType = 'Adresse partenariat';
                    break;
                case self::CONTACT_ROLE_OTHER:
                default:
                    $settingType = 'Adresse autre';
                    break;
            }

            $recipient = $this->get('doctrine.orm.entity_manager')
                ->getRepository(Settings::class)
                ->findOneBy(['type' => $settingType])->getValue();

            $infos = '<ul>';
            $infos .= '<li>Type demande : ' . $objets[$post['role']] . '</li>';
            $infos .= '<li>Nom : ' . $ficelle->speChar2HtmlEntities($post['lastname']) . '</li>';
            $infos .= '<li>Prenom : ' . $ficelle->speChar2HtmlEntities($post['firstname']) . '</li>';
            $infos .= '<li>Email : ' . $ficelle->speChar2HtmlEntities($post['email']) . '</li>';
            $infos .= '<li>telephone : ' . $ficelle->speChar2HtmlEntities($post['phone']) . '</li>';
            $infos .= '<li>Societe : ' . $ficelle->speChar2HtmlEntities($post['company']) . '</li>';
            $infos .= '<li>Message : ' . $ficelle->speChar2HtmlEntities($post['body']) . '</li>';
            $infos .= '</ul>';

            $variablesInternalMail = array(
                '$surl'   => $this->get('assets.packages')->getUrl(''),
                '$url'    => $this->get('assets.packages')->getUrl(''),
                '$email'  => $post['email'],
                '$nom'    => $post['lastname'],
                '$prenom' => $post['firstname'],
                '$objet'  => $objets[$post['role']],
                '$infos'  => $infos
            );

            /** @var \Unilend\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact', $variablesInternalMail, false);
            try {
                $message->setTo($recipient);
                $message->setReplyTo([$post['email'] => $post['firstname'] . ' ' . $post['lastname']]);
                $mailer = $this->get('mailer');
                $mailer->send($message);
            } catch (\Exception $exception) {
                $this->get('logger')->error(
                    'Could not send email : notification-demande-de-contact - Exception: ' . $exception->getMessage(),
                    ['id_mail_template' => $message->getTemplateId(), 'email address' => $recipient, 'email_details' => $variablesInternalMail, 'class' => __CLASS__, 'function' => __FUNCTION__]
                );
            }

            $this->addFlash('contactSuccess', $translator->trans('contact_confirmation'));
        }
    }
}
