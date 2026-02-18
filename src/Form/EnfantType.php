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
            ->add('firstName', TextType::class, [
                'label'    => 'Prénom',
                'required' => true,
            ])
            ->add('lastName', TextType::class, [
                'label'    => 'Nom',
                'required' => true,
            ])
            ->add('username', TextType::class, [
                'label'    => 'Identifiant de connexion',
                'required' => true,
                'help'     => 'Lettres, chiffres et underscores uniquement. Ex: alice2024',
                'attr'     => ['placeholder' => 'Ex: alice2024'],
            ])
            ->add('birthDate', DateType::class, [
                'label'    => 'Date de naissance',
                'widget'   => 'single_text',
                'required' => true,
                'html5'    => true,
            ])
            ->add('niveau', ChoiceType::class, [
                'label'    => 'Niveau scolaire',
                'required' => true,
                'choices'  => [
                    'Maternelle' => 'maternelle',
                    'CP'         => 'cp',
                    'CE1'        => 'ce1',
                    'CE2'        => 'ce2',
                    'CM1'        => 'cm1',
                    'CM2'        => 'cm2',
                    '6ème'       => '6eme',
                    '5ème'       => '5eme',
                    '4ème'       => '4eme',
                    '3ème'       => '3eme',
                ],
            ])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'mapped'          => false,
                'first_options'   => [
                    'label' => 'Mot de passe',
                    'help'  => 'Minimum 6 caractères.',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'second_options'  => [
                    'label' => 'Confirmer le mot de passe',
                    'attr'  => ['autocomplete' => 'new-password'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => [
                    new NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                    new Length([
                        'min'        => 6,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => User::class,
            'validation_groups' => ['enfant_creation'],
        ]);
    }
}