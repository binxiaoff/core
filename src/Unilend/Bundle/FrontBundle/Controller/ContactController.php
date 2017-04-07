<?php

namespace Unilend\Bundle\FrontBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Service\SearchService;
use Unilend\Bundle\FrontBundle\Security\User\BaseUser;
use Unilend\Bundle\FrontBundle\Security\User\UserBorrower;
use Unilend\Bundle\FrontBundle\Security\User\UserLender;
use Unilend\core\Loader;

class ContactController extends Controller
{
    /**
     * @Route("/contact", name="contact")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function contactAction(Request $request)
    {
        $template = $this->get('session')->get('searchResult', ['query' => '', 'results' => '']);
        $this->get('session')->remove('searchResult');

        /** @var BaseUser $user */
        $user = $this->getUser();

        if ($user instanceof UserLender || $user instanceof UserBorrower) {
            /** @var \clients $client */
            $client = $this->get('unilend.service.entity_manager')->getRepository('clients');
            $client->get($user->getClientId());

            if (in_array($client->type, [Clients::TYPE_LEGAL_ENTITY, Clients::TYPE_LEGAL_ENTITY_FOREIGNER]) || $user instanceof UserBorrower) {
                /** @var \companies $company */
                $company = $this->get('unilend.service.entity_manager')->getRepository('companies');
                $company->get($client->id_client, 'id_client_owner');
            }

            $template['formData'] = [
                'firstname' => $client->prenom,
                'lastname'  => $client->nom,
                'phone'     => $client->mobile,
                'email'     => $client->email,
                'company'   => isset($company) ? $company->name : '',
                'role'      => $user instanceof UserLender ? 2 : ($user instanceof UserBorrower) ? 3 : ''
            ];
        }

        if ($request->request->has('message')) {
            $post = $request->request->get('message');
            $this->contactForm($post);
            if ($this->get('session')->getFlashBag()->has('contactErrors')) {
                $template['formData'] = $post;
            }
        }

        return $this->render('pages/static_pages/contact.html.twig', $template);
    }

    /**
     * @Route("/contact/search", name="contact_search")
     * @Method({"POST"})
     *
     * @return Response
     */
    public function searchAction(Request $request)
    {
        return $this->redirectToRoute('contact_search_result', ['query' => urlencode($request->request->get('search'))]);
    }

    /**
     * @Route("/contact/search/{query}", name="contact_search_result")
     * @Method({"GET"})
     *
     * @param  string $query
     * @return Response
     */
    public function resultAction($query)
    {
        /** @var SearchService $search */
        $search = $this->get('unilend.service.search_service');
        $query  = filter_var(urldecode($query), FILTER_SANITIZE_STRING);

        /** @var BaseUser $user */
        $user = $this->getUser();

        $isFullyConnectedUser = ($user instanceof UserLender && $user->getClientStatus() == \clients_status::VALIDATED || $user instanceof UserBorrower);

        $this->get('session')->set('searchResult',[
            'query'   => $query,
            'results' => $search->search($query, $isFullyConnectedUser)
        ]);

        return $this->redirectToRoute('contact');
    }

    private function contactForm($post)
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
            /** @var \settings $settings */
            $settings = $this->get('unilend.service.entity_manager')->getRepository('settings');
            /** @var \ficelle $ficelle */
            $ficelle = Loader::loadLib('ficelle');
            $objets = ['', 'Relation presse', 'Demande preteur', 'Demande Emprunteur', 'Recrutement', 'Autre', 'Partenariat'];

            $settings->get('Facebook', 'type');
            $lien_fb = $settings->value;

            $settings->get('Twitter', 'type');
            $lien_tw = $settings->value;

            $varMail = array(
                'surl'     => $this->get('assets.packages')->getUrl(''),
                'url'      => $this->get('assets.packages')->getUrl(''),
                'email_c'  => $post['email'],
                'prenom_c' => $post['firstname'],
                'nom_c'    => $post['lastname'],
                'objet'    => $objets[$post['role']],
                'projets'  => $this->generateUrl('projects_list'),
                'lien_fb'  => $lien_fb,
                'lien_tw'  => $lien_tw
            );

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('demande-de-contact', $varMail);
            $message->setTo($post['email']);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            switch ($post['role']) {
                case 1:
                    $settings->get('Adresse presse', 'type');
                    break;
                case 2:
                    $settings->get('Adresse preteur', 'type');
                    break;
                case 3:
                    $settings->get('Adresse emprunteur', 'type');
                    break;
                case 4:
                    $settings->get('Adresse recrutement', 'type');
                    break;
                case 5:
                    $settings->get('Adresse autre', 'type');
                    break;
                case 6:
                    $settings->get('Adresse partenariat', 'type');
                    break;
                default:
                    $settings->get('Adresse autre', 'type');
                    break;
            }

            $destinataire = $settings->value;

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

            /** @var \Unilend\Bundle\MessagingBundle\Bridge\SwiftMailer\TemplateMessage $message */
            $message = $this->get('unilend.swiftmailer.message_provider')->newMessage('notification-demande-de-contact', $variablesInternalMail, false);
            $message->setTo($destinataire);
            $message->setReplyTo([$post['email'] => $post['firstname'] . ' ' . $post['lastname']]);
            $mailer = $this->get('mailer');
            $mailer->send($message);

            $this->addFlash('contactSuccess', $translator->trans('contact_confirmation'));
        }
    }
}
