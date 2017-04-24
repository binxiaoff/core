<?php

namespace Unilend\Bundle\FrontBundle\Form\Components;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Unilend\Bundle\CoreBusinessBundle\Service\LocationManager;

class CountriesType extends AbstractType
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
        $countryList = $this->locationManager->getCountries();

        $resolver->setDefaults([
            'choices'  => array_flip($countryList),
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
