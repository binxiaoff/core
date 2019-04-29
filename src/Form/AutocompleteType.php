<?php

declare(strict_types=1);

namespace Unilend\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\{AbstractType, FormBuilderInterface, FormInterface, FormView};
use Symfony\Component\OptionsResolver\OptionsResolver;
use Unilend\Form\DataTransformer\IdentityTransformer;

class AutocompleteType extends AbstractType
{
    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    /**
     * AutocompleteType constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer(new IdentityTransformer($this->managerRegistry, $options['class']));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'required'    => false,
            'placeholder' => null,
            'attr'        => ['width' => '100%'],
        ]);
        $resolver->setRequired('class');
        $resolver->setRequired('url');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return TextType::class;
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['url']         = $options['url'];
        $view->vars['placeholder'] = $options['placeholder'];
    }
}
