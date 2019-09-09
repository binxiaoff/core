<?php

declare(strict_types=1);

namespace Unilend\Form\User;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Valid;

class InitProfileType extends AbstractType
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * InitProfileType constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('securityQuestion', SecurityQuestionType::class, [
                'mapped'      => false,
                'constraints' => [new Valid()],
            ])
            ->add('password', PasswordType::class, [
                'mapped'      => false,
                'constraints' => [new Valid()],
            ])
            ->add('cgu', CheckboxType::class, [
                'mapped'                       => false,
                'constraints'                  => [new IsTrue(['message' => 'account_initialization.cgu.true'])],
                'label'                        => 'account-init.cgu-label',
                'label_translation_parameters' => [
                    '%currentCGUPath%' => $this->router->generate('service_terms'),
                ],
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return IdentityType::class;
    }
}
