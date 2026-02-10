<?php

namespace App\Form;

use App\Entity\Game;
use App\Entity\Level;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class GameType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];
        $hasTemp = $options['has_temp'];

        $imageConstraints = [
            new Assert\File([
                'maxSize' => '2M',
                'mimeTypes' => [
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'image/gif',
                ],
                'mimeTypesMessage' => 'Please upload a valid image (.jpeg, .png, .webp, .gif).',
            ]),
        ];

        // Image required only if new AND no temp image
        if (!$isEdit && !$hasTemp) {
            $imageConstraints[] = new Assert\NotBlank([
                'message' => 'Please select an image.',
            ]);
        }

        $builder
            ->add('name', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'The name must contain letters only.',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'Game name is required.',
                    ]),
                    new Assert\Length([
                        'min' => 3,
                        'minMessage' => 'Minimum 3 characters required.',
                    ])
                ]
            ])

            ->add('type', TextType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'The type must contain letters only.',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'Game type is required.',
                    ])
                ]
            ])

            ->add('description', TextareaType::class, [
                'empty_data' => '',
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => '/^[\p{L}\s]+$/u',
                        'message' => 'Description must contain letters and spaces only.',
                    ]),
                    new Assert\NotBlank([
                        'message' => 'Description is required.',
                    ]),
                    new Assert\Length([
                        'min' => 5,
                        'minMessage' => 'Minimum 5 characters required.',
                    ])
                ]
            ])

            ->add('imageTemp', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            ->add('image', FileType::class, [
                'label' => 'Image',
                'mapped' => false,
                'required' => (!$isEdit && !$hasTemp),
                'constraints' => $imageConstraints,
            ])

            ->add('idLevel', EntityType::class, [
                'class' => Level::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a level',
                'constraints' => [
                    new Assert\NotNull([
                        'message' => 'Please select a level.',
                    ])
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Game::class,
            'is_edit' => false,
            'has_temp' => false,
        ]);
    }
}
