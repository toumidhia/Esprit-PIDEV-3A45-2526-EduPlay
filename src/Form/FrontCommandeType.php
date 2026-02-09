<?php

namespace App\Form;

use App\Entity\Commande;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class FrontCommandeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $parents = $options['parents'] ?? [];


        $builder
            ->add('idUser', EntityType::class, [
                'label' => 'Vous êtes (parent)',
                'class' => User::class,
                'choices' => $parents,
                'choice_label' => function(User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'placeholder' => '-- Choisir votre compte --',
                'required' => true,
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Veuillez sélectionner votre compte.',
                    ]),
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité',
                'required' => true,
                'attr' => [
                    'min' => 1,
                    'max' => 100,
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
            'parents' => [], // Liste des utilisateurs parents
        ]);
    }
}