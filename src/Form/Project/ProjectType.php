<?php

declare(strict_types=1);

namespace Unilend\Form\Project;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Unilend\Entity\{Clients, Companies, MarketSegment, Project};
use Unilend\Form\Company\CompanyAutocompleteType;
use Unilend\Form\Traits\ConstantsToChoicesTrait;
use Unilend\Form\Tranche\TrancheType;

class ProjectType extends AbstractType
{
    use ConstantsToChoicesTrait;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * AutocompleteType constructor.
     *
     * @param ManagerRegistry       $managerRegistry
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ManagerRegistry $managerRegistry, TokenStorageInterface $tokenStorage)
    {
        $this->managerRegistry = $managerRegistry;
        $this->tokenStorage    = $tokenStorage;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentCompanyId = $this->getCurrentCompanyId();

        $builder
            ->add('title', TextType::class, ['label' => 'project-form.title'])
            ->add('borrowerCompany', CompanyAutocompleteType::class, ['label' => 'project-form.borrower-company'])
            ->add('marketSegment', EntityType::class, [
                'label' => 'project-form.market-segment',
                'class' => MarketSegment::class,
            ])
            ->add('replyDeadline', DateType::class, [
                'label'  => 'project-form.replay-deadline',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'ui-has-datepicker'],
            ])
            ->add('expectedClosingDate', DateType::class, [
                'label'  => 'project-form.expected-closing-date',
                'widget' => 'single_text',
                'input'  => 'datetime_immutable',
                'attr'   => ['class' => 'ui-has-datepicker'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'project-form.description',
                'attr'  => ['row' => 10],
            ])
            ->add('tranches', CollectionType::class, [
                'label'          => false,
                'entry_type'     => TrancheType::class,
                'entry_options'  => ['label' => false],
                'allow_add'      => true,
                'allow_delete'   => true,
                'by_reference'   => false,
                'prototype'      => true,
                'prototype_name' => '__tranche__',
                'attr'           => ['class' => 'tranches'],
            ])
            ->add('foncarisGuarantee', ChoiceType::class, [
                'label'   => 'project-form.foncaris-guarantee',
                'choices' => $this->getChoicesFromConstants(Project::getFoncarisGuaranteeOptions(), 'foncaris-guarantee'),
            ])
            ->add('arranger', EntityType::class, [
                'label'         => 'project-form.arranger',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => function (EntityRepository $entityRepository) use ($currentCompanyId) {
                    return $entityRepository->createQueryBuilder('c')
                        ->where('c.idCompany in (:arrangersToSelect)')
                        ->setParameter('arrangersToSelect', array_merge(Companies::COMPANY_ELIGIBLE_ARRANGER, [$currentCompanyId]))
                        ;
                },
            ])
            ->add('run', EntityType::class, [
                'label'         => 'project-form.run',
                'required'      => false,
                'class'         => Companies::class,
                'query_builder' => function (EntityRepository $entityRepository) {
                    return $entityRepository->createQueryBuilder('c')
                        ->where('c.idCompany in (:runsToSelect)')
                        ->orWhere('c.parent in (:runsParantToSelect)')
                        ->setParameters(['runsToSelect' => Companies::COMPANY_ELIGIBLE_RUN, 'runsParantToSelect' => Companies::COMPANY_SUBSIDIARY_ELIGIBLE_RUN])
                        ;
                },
            ])
            ->add('projectAttachments', CollectionType::class, [
                'label'         => false,
                'entry_type'    => ProjectAttachmentType::class,
                'entry_options' => ['label' => false],
                'allow_add'     => true,
                'allow_delete'  => true,
                'by_reference'  => false,
                'prototype'     => true,
                'attr'          => ['class' => 'attachments'],
            ])
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
     * @return int|null
     */
    private function getCurrentCompanyId(): ?int
    {
        $companyId = null;
        /** @var Clients $user */
        $user = $this->tokenStorage->getToken()->getUser();

        if ($user && $user->getCompany()) {
            $companyId = $user->getCompany()->getIdCompany();
        }

        return $companyId;
    }
}
