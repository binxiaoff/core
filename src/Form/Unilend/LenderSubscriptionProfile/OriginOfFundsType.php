<?php


namespace Unilend\Form\Unilend\LenderSubscriptionProfile;


use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Entity\Clients;
use Unilend\Entity\Settings;

class OriginOfFundsType extends AbstractType
{
    /** @var  EntityManagerInterface */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Clients $clientEntity */
        $clientEntity = $builder->getData();

        $fundsOrigin = $this->getFundsOrigin($clientEntity->getType());

        $builder
            ->add('fundsOrigin', ChoiceType::class, [
                'choices'     => array_flip($fundsOrigin),
                'expanded'    => false,
                'multiple'    => false,
                'placeholder' => ''
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Unilend\Entity\Clients'
        ]);
    }


    /**
     * @param $clientType
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getFundsOrigin($clientType)
    {
        switch ($clientType) {
            case Clients::TYPE_PERSON:
            case Clients::TYPE_PERSON_FOREIGNER:
                $setting = $this->entityManager->getRepository(Settings::class)->findOneByType('Liste deroulante origine des fonds');
                break;
            case Clients::TYPE_LEGAL_ENTITY:
            case Clients::TYPE_LEGAL_ENTITY_FOREIGNER;
                $setting = $this->entityManager->getRepository(Settings::class)->findOneByType('Liste deroulante origine des fonds societe');
                break;
            default:
                throw new \Exception('Client type not supported for origin of funds list');
        }
        $fundsOriginList = explode(';', $setting->getValue());
        return array_combine(range(1, count($fundsOriginList)), array_values($fundsOriginList));
    }

}
