<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, ChoiceType, DateType, TextType, TextareaType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormError, FormEvent, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, Companies, MarketSegment, Project};
use Unilend\Form\{Company\CompanyAutocompleteType, Tranche\TrancheTypeCollectionType};
use Unilend\Repository\CompaniesRepository;

class ProjectType extends AbstractType
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TranslatorInterface */
    private $translator;

    /**
     * @param ManagerRegistry       $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     * @param TranslatorInterface   $translator
     */
    public function __construct(ManagerRegistry $managerRegistry, TokenStorageInterface $tokenStorage, TranslatorInterface $translator)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tokenStorage    = $tokenStorage;
        $this->translator      = $translator;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentCompany = $this->getCurrentCompany();

        $builder
            ->add('title', TextType::class, ['label' => 'project-form.title'])
            ->add('borrowerCompany', CompanyAutocompleteType::class, ['label' => 'project-form.borrower-company'])
            ->add('borrowerCompanyCreationInProgress', CheckboxType::class, [
                'mapped'   => false,
                'label'    => 'project-form.borrower-company-creation-in-progress',
                'required' => false,
            ])
            ->add('marketSegment', EntityType::class, [
                'label'        => 'project-form.market-segment',
                'choice_label' => function (MarketSegment $marketSegment, $key, $value) {
                    return 'market-segment.' . $marketSegment->getLabel();
                },
                'class'                     => MarketSegment::class,
                'choice_translation_domain' => true,
                'placeholder'               => '',
            ])
            ->add('replyDeadline', DateType::class, [
                'label'    => 'project-form.replay-deadline',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('expectedClosingDate', DateType::class, [
                'label'    => 'project-form.expected-closing-date',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'project-form.description',
                'attr'  => ['rows' => 6],
            ])
            ->add('tranches', TrancheTypeCollectionType::class)
            ->add('foncarisGuarantee', ChoiceType::class, [
                'label'        => 'project-form.foncaris-guarantee',
                'required'     => false,
                'choices'      => Project::getFoncarisGuaranteeOptions(),
                'choice_label' => function ($option, string $key, string $value) {
                    return 'foncaris-guarantee.' . mb_strtolower($key);
                },
            ])
            ->add('arranger', EntityType::class, [
                'label'         => 'project-form.arranger',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => function (CompaniesRepository $companyRepository) use ($currentCompany) {
                    return $companyRepository->createEligibleArrangersQB($currentCompany, ['name' => 'ASC']);
                },
            ])
            ->add('run', EntityType::class, [
                'label'         => 'project-form.run',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => function (CompaniesRepository $companyRepository) {
                    return $companyRepository->createEligibleRunQB(['name' => 'ASC']);
                },
            ])
            ->add('projectAttachments', ProjectAttachmentCollectionType::class)
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'handleBorrowerCompany'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Project::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'project_creation_type';
    }

    /**
     * @param FormEvent $formEvent
     */
    public function handleBorrowerCompany(FormEvent $formEvent): void
    {
        /** @var Project $project */
        $project = $formEvent->getData();
        if ($project->getBorrowerCompany()) {
            return;
        }

        if ($formEvent->getForm()->get('borrowerCompanyCreationInProgress')->getData()) {
            $project->setBorrowerCompany((new Companies())->setName($this->translator->trans('company.' . Companies::TRANSLATION_CREATION_IN_PROGRESS)));

            return;
        }

        $formEvent->getForm()->get('borrowerCompany')->addError(new FormError($this->translator->trans('project-form.borrower-company-required')));
    }

    /**
     * @return Companies|null
     */
    private function getCurrentCompany(): ?Companies
    {
        $company = null;
        /** @var Clients $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user && $user->getCompany()) {
            $company = $user->getCompany();
        }

        return $company;
    }
}
