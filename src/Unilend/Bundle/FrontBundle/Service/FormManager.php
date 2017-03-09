<?php


namespace Unilend\Bundle\FrontBundle\Service;


use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\BankAccount;
use Unilend\Bundle\CoreBusinessBundle\Entity\Clients;
use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsAdresses;
use Unilend\Bundle\CoreBusinessBundle\Entity\Companies;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\BankAccountType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\CompanyIdentityType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\LegalEntityType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\OriginOfFundsType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonFiscalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PersonType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\PostalAddressType;
use Unilend\Bundle\FrontBundle\Form\LenderSubscriptionProfile\SecurityQuestionType;

class FormManager
{

    /** @var FormFactory  */
    private $formFactory;
    /** @var TranslatorInterface  */
    private $translator;

    /**
     * FormManager constructor.
     * @param FormFactory $formFactory
     * @param TranslatorInterface $translator
     */
    public function __construct(FormFactory $formFactory, TranslatorInterface $translator)
    {
        $this->formFactory = $formFactory;
        $this->translator = $translator;
    }

    /**
     * @param object $dbObject
     * @param object $formObject
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getModifiedContent($dbObject, $formObject)
    {
        if (get_class($dbObject) !== get_class($formObject)) {
            throw new \Exception('The objects to be compared are not of the same class');
        }

        $differences = [];
        $object      = new \ReflectionObject($dbObject);

        foreach ($object->getMethods() as $method) {
            if (
                substr($method->name, 0, 3) === 'get'
                && $method->invoke($dbObject) != $method->invoke($formObject)
            ) {
                $differences[] = str_replace('get', '', $method->name);
            }
        }

        return $differences;
    }

    /**
     * @param Clients         $client
     * @param ClientsAdresses $clientAddress
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getLenderSubscriptionPersonIdentityForm(Clients $client, ClientsAdresses $clientAddress)
    {
        $form= $this->formFactory->createBuilder()
            ->add('client', PersonType::class, ['data' => $client])
            ->add('fiscalAddress', PersonFiscalAddressType::class, ['data' => $clientAddress])
            ->add('postalAddress', PostalAddressType::class, ['data' => $clientAddress])
            ->add('security', SecurityQuestionType::class, ['data' => $client])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $this->translator->trans('lender-subscription_identity-client-type-person-label') => 'person',
                    $this->translator->trans('lender-subscription_identity-client-type-legal-entity-label')   => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'person'
            ])
            ->add('tos', CheckboxType::class)
            ->getForm();

        return $form;
    }

    /**
     * @param Clients         $client
     * @param Companies       $company
     * @param ClientsAdresses $clientAddress
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getLenderSubscriptionLegalEntityIdentityForm(Clients $client, Companies $company, ClientsAdresses $clientAddress)
    {
        $form = $this->formFactory->createBuilder()
            ->add('client', LegalEntityType::class, ['data' => $client])
            ->add('company', CompanyIdentityType::class, ['data' => $company])
            ->add('fiscalAddress', CompanyAddressType::class, ['data' => $company])
            ->add('postalAddress', PostalAddressType::class, ['data' => $clientAddress])
            ->add('security', SecurityQuestionType::class, ['data' => $client])
            ->add('clientType', ChoiceType::class, [
                'choices'  => [
                    $this->translator->trans('lender-subscription_identity-client-type-person-label') => 'person',
                    $this->translator->trans('lender-subscription_identity-client-type-legal-entity-label')   => 'legalEntity'
                ],
                'expanded' => true,
                'multiple' => false,
                'data' => 'legalEntity'
            ])
            ->add('tos', CheckboxType::class)
            ->getForm();

        return $form;
    }

    public function getBankInformationForm(BankAccount $bankAccount, Clients $client)
    {
        $form = $this->formFactory->createBuilder()
            ->add('bankAccount', BankAccountType::class, ['data' => $bankAccount])
            ->add('client', OriginOfFundsType::class, ['data' => $client])
            ->add('housedByThirdPerson', CheckboxType::class, [
                'required' => false,
            ])
            ->getForm();

        return $form;
    }

    /**
     * @param  array $post
     *
     * @return mixed
     */
    public function cleanPostData($post)
    {
        foreach ($post as $key => $value) {
            if (is_array($value)) {
                $this->cleanPostData($value);
            }

            if (is_string($value)) {
                $post[$key] = htmlspecialchars(strip_tags($value));
            }
        }

        return $post;
    }
}
