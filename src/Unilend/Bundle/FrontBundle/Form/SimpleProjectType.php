<?php
namespace Unilend\Bundle\FrontBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;

class SimpleProjectType extends AbstractType
{
    /** @var EntityManager */
    private $entityManager;
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(EntityManager $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $settings = $this->entityManager->getRepository('settings');
        $settings->get('Durée des prêts autorisées', 'type');
        $possibleDuration = explode(',', $settings->value);
        $durationLabel = array_map(function($duration){
            $month = $this->translator->transChoice('common_month', $duration, ['%count%' => $duration]);
            return $month;
        }, $possibleDuration);
        $builder
            ->add('amount', IntegerType::class, [
                'label'    => 'borrower-demand_amount',
                'required' => true,
                'attr' => [
                    'placeholder' => 'borrower-demand_amount-placeholder',
                ]
            ])
            ->add('duration', ChoiceType::class, [
                'label'    => 'borrower-demand_duration',
                'required' => true,
                'choices'  => array_combine($durationLabel, $possibleDuration)
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'borrower-demand_project-description',
                'required' => true,
                'attr' => [
                    'placeholder' => 'borrower-demand_project-description-placeholder',
                    'rows' => 6,
                ]
            ]);
    }
}
