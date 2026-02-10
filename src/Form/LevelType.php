<?php

namespace App\Form;

use App\Entity\Level;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class LevelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Level name is required.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'Name must contain letters only.',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Minimum 3 characters required.',
                        'max' => 255,
                    ]),
                ],
            ])

            ->add('description', TextareaType::class, [
                'required' => false,
                'empty_data' => '',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'Description must contain only letters and spaces.',
                    ]),
                    
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Minimum 10 characters required.',
                        'max' => 1000,
                    ]),
                ],
            ])

            ->add('difficulty', IntegerType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Difficulty is required and must contain only numbers.',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 5,
                        'notInRangeMessage' => 'Difficulty must be between {{ min }} and {{ max }}.',
                    ]),
                    
                    
                ],
            ])

            ->add('minAge', IntegerType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Minimum age is required.',
                    ]),
                    new Assert\Range([
                        'min' => 1,
                        'max' => 18,
                        'notInRangeMessage' => 'Minimum age must be between {{ min }} and {{ max }}.',
                    ]),
                ],
            ])

            ->add('maxAge', IntegerType::class, [
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Maximum age is required.',
                    ]),
                    new Assert\Range([
                        'min' => 3,
                        'max' => 16,
                        'notInRangeMessage' => 'Maximum age must be between {{ min }} and {{ max }}.',
                    ]),
                ],
            ])

            ->add('pedagGoal', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'Educational goal must contain letters only.',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'Educational goal is required.',
                    ]),
                    new Assert\Length([
                        'min' => 4,
                        'minMessage' => 'Minimum 4 characters required.',
                        'max' => 255,
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Level::class,
        ]);
    }
}
