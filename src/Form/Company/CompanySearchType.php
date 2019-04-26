<?php

declare(strict_types=1);

namespace Unilend\Form\Company;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Unilend\Form\DataTransformer\SirenToCompanyTransformer;
use Unilend\Repository\CompaniesRepository;

class CompanySearchType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var CompaniesRepository
     */
    private $companiesRepository;

    /**
     * CompanySearchType constructor.
     *
     * @param RouterInterface     $router
     * @param CompaniesRepository $companiesRepository
     */
    public function __construct(RouterInterface $router, CompaniesRepository $companiesRepository)
    {
        $this->router              = $router;
        $this->companiesRepository = $companiesRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new SirenToCompanyTransformer($this->companiesRepository));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'attr' => [
                'class'                 => 'input-company-autocomplete',
                'data-autocomplete-url' => $this->router->generate('company_search_by_siren'),
                'placeholder'           => 'company-search-form.siren-place-holder',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'company_search_type';
    }
}
