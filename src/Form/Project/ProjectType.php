<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, ChoiceType, DateType, TextType, TextareaType};
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormError, FormEvent, FormEvents};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;
use Unilend\Entity\{Clients, Companies, MarketSegment, Project};
use Unilend\Form\{Company\CompanyAutocompleteType, Tranche\TrancheTypeCollectionType};
use Unilend\Repository\{CompaniesRepository, MarketSegmentRepository};

class ProjectType extends AbstractType
{
    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TranslatorInterface */
    private $translator;

    /** @var CompaniesRepository */
    private $companyRepository;

    /** @var MarketSegmentRepository */
    private $marketSegmentRepository;

    /**
     * @param TokenStorageInterface   $tokenStorage
     * @param TranslatorInterface     $translator
     * @param CompaniesRepository     $companyRepository
     * @param MarketSegmentRepository $marketSegmentRepository
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        CompaniesRepository $companyRepository,
        MarketSegmentRepository $marketSegmentRepository
    ) {
        $this->tokenStorage            = $tokenStorage;
        $this->translator              = $translator;
        $this->companyRepository       = $companyRepository;
        $this->marketSegmentRepository = $marketSegmentRepository;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentCompany       = $this->getCurrentCompany();
        $arrangerQueryBuilder = $this->companyRepository->createEligibleArrangersQB($currentCompany, ['name' => 'ASC']);
        $runAgentQueryBuilder = $this->companyRepository->createEligibleRunAgentQB(['name' => 'ASC']);

        $marketSegments = $this->marketSegmentRepository->findAll();
        usort($marketSegments, function ($first, $second) {
            return strcmp(
                $this->translator->trans('market-segment.' . $first->getLabel()),
                $this->translator->trans('market-segment.' . $second->getLabel())
            );
        });

        $builder
            ->add('title', TextType::class, ['label' => 'project-form.title-label'])
            ->add('borrowerCompany', CompanyAutocompleteType::class, ['label' => 'project-form.borrower-company-label'])
            ->add('borrowerCompanyCreationInProgress', CheckboxType::class, [
                'mapped'   => false,
                'label'    => 'project-form.borrower-company-creation-in-progress-label',
                'required' => false,
            ])
            ->add('marketSegment', EntityType::class, [
                'label'        => 'project-form.market-segment-label',
                'class'        => MarketSegment::class,
                'choices'      => $marketSegments,
                'choice_label' => function (MarketSegment $marketSegment, $key, $value) {
                    return 'market-segment.' . $marketSegment->getLabel();
                },
                'choice_translation_domain' => true,
                'placeholder'               => '',
            ])
            ->add('replyDeadline', DateType::class, [
                'label'    => 'project-form.replay-deadline-label',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('expectedClosingDate', DateType::class, [
                'label'    => 'project-form.expected-closing-date-label',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('lenderConsultationClosingDate', DateType::class, [
                'label'    => 'project-form.lender-consultation-date-label',
                'required' => false,
                'widget'   => 'single_text',
                'input'    => 'datetime_immutable',
                'format'   => 'dd/MM/yyyy',
                'attr'     => ['class' => 'ui-has-datepicker'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'project-form.description-label',
                'attr'  => ['rows' => 6],
            ])
            ->add('tranches', TrancheTypeCollectionType::class, [
                'constraints'   => [new Valid()],
                'entry_options' => ['rate_required' => Project::OPERATION_TYPE_SYNDICATION === $options['operation_type']],
            ])
            ->add('arranger', EntityType::class, [
                'label'         => 'project-form.arranger-label',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => $arrangerQueryBuilder,
            ])
            ->add('deputyArranger', EntityType::class, [
                'label'         => 'project-form.deputy-arranger-label',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => $arrangerQueryBuilder,
            ])
            ->add('run', EntityType::class, [
                'label'         => 'project-form.run-label',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => $runAgentQueryBuilder,
            ])
            ->add('loanOfficer', EntityType::class, [
                'label'         => 'project-form.loan-officer-label',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => $runAgentQueryBuilder,
            ])
            ->add('securityTrustee', EntityType::class, [
                'label'         => 'project-form.security-trustee-label',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => $runAgentQueryBuilder,
            ])
            ->add('offerVisibility', ChoiceType::class, [
                'label'             => 'project-form.offer-visibility-label',
                'required'          => true,
                'preferred_choices' => [Project::OFFER_VISIBILITY_PRIVATE],
                'choices'           => Project::getAllOfferVisibilities(),
                'choice_label'      => static function ($option, string $key, string $value) {
                    return 'project-form.offer-visibility-choice-' . $value . '-label';
                },
            ])
            ->add('projectAttachments', ProjectAttachmentCollectionType::class, ['constraints' => [new Valid()]])
            ->add('projectFees', ProjectFeeTypeCollectionType::class)
            ->addEventListener(FormEvents::POST_SUBMIT, [$this, 'handleCreationInProgressCompany'])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', Project::class);
        $resolver->setRequired(['operation_type']);
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
    public function handleCreationInProgressCompany(FormEvent $formEvent): void
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
