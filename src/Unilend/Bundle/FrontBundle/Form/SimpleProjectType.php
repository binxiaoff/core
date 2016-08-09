<?php
namespace Unilend\Bundle\FrontBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Unilend\Bundle\CoreBusinessBundle\Service\Simulator\EntityManager;
use Unilend\Bundle\TranslationBundle\Service\TranslationManager;

class SimpleProjectType extends AbstractType
{
    /** @var EntityManager */
    private $entityManager;
    private $translator;

    public function __construct(EntityManager $entityManager, TranslationManager $translator)
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
            $month = $this->translator->selectTranslation('borrower-projects', 'month');
            return $duration . ' ' . $month;
        }, $possibleDuration);
        $builder
            ->add('amount', IntegerType::class, [
                'label'    => 'borrower-demand_wanted-amount',
                'required' => true,
                'attr' => [
                    'placeholder' => 'borrower-demand_amount-placeholder-msg',
                ]
            ])
            ->add('duration', ChoiceType::class, [
                'label'    => 'borrower-demand_duration',
                'required' => true,
                'choices'  => array_combine($durationLabel, $possibleDuration)
            ])
            ->add('message', TextareaType::class, [
                'label'    => 'borrower-demand_describe-your-project',
                'required' => true,
                'attr' => [
                    'placeholder' => 'borrower-demand_describe-your-project-placeholder-msg',
                    'rows' => 6,
                ]
            ]);
    }
}
