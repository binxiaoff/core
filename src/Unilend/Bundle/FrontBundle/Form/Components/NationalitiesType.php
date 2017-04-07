<?php

namespace Unilend\Bundle\FrontBundle\Form\Components;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;

class NationalitiesType extends AbstractType
{
    /** @var  LocationManager */
    private $locationManager;

    /**
     * CountriesType constructor.
     * @param LocationManager $locationManager
     */
    public function __construct(LocationManager $locationManager)
    {
        $this->locationManager = $locationManager;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $nationalityList = $this->locationManager->getNationalities();

        $resolver->setDefaults([
            'choices' => array_flip($nationalityList),
            'expanded' => false,
            'multiple' => false
        ]);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

}
