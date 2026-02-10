<?php

namespace App\Form;

use App\Entity\Commande;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FrontCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'class' => 'quantity-input',
                ],
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'La quantité est obligatoire.',
                    ]),
                    new Assert\Type(['type' => 'integer', 'message' => 'La quantité doit être un entier.']),
                    new Assert\Positive([
                        'message' => 'La quantité doit être positive.',
                    ]),
                    new Assert\LessThanOrEqual([
                        'value' => 100,
                        'message' => 'La quantité ne peut pas dépasser {{ compared_value }}.',
                    ]),
                ],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commande::class,
            // keep compatibility with callers that pass these options
            'parents' => [],
            'current_user_is_parent' => false,
        ]);
    }
}