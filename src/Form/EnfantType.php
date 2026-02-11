<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class EnfantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le nom']),
                ],
            ])
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer le prénom']),
                ],
            ])
            ->add('username', TextType::class, [
                'label' => 'Identifiant de connexion',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un identifiant']),
                    new Length([
                        'min' => 4,
                        'minMessage' => 'L\'identifiant doit contenir au moins {{ limit }} caractères',
                    ])
                ],
                'help' => 'Cet identifiant sera utilisé par votre enfant pour se connecter'
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer la date de naissance']),
                ],
            ])
            ->add('niveau', ChoiceType::class, [
                'label' => 'Niveau scolaire',
                'choices' => [
                    'Maternelle' => 'Maternelle',
                    'CP' => 'CP',
                    'CE1' => 'CE1',
                    'CE2' => 'CE2',
                    'CM1' => 'CM1',
                    'CM2' => 'CM2',
                    '6ème' => '6ème',
                    '5ème' => '5ème',
                ],
                'required' => false,
                'placeholder' => 'Sélectionner un niveau',
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                    new Length([
                        'min' => 4,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères',
                    ])
                ],
                'help' => 'Choisissez un mot de passe simple que votre enfant pourra retenir'
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}