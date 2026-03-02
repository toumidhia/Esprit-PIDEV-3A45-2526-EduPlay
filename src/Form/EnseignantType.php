<?php

namespace App\Form;

use App\Entity\User;
use Karser\Recaptcha3Bundle\Form\Recaptcha3Type;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<User>
 */
class EnseignantType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'required' => true,
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('specialite', TextType::class, [
                'label' => 'Spécialité',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Mathématiques, Français, etc.'
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => true,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password',
                        'minlength' => 6
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => [
                        'class' => 'form-control',
                        'autocomplete' => 'new-password'
                    ]
                ],
                'invalid_message' => 'Les mots de passe doivent correspondre',
            ])
            // ✅ CAPTCHA AJOUTÉ
            ->add('captcha', Recaptcha3Type::class, [
                'constraints' => new Recaptcha3(),
                'action_name' => 'create_teacher',
                'locale' => 'fr',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => false, 
        ]);
    }
}