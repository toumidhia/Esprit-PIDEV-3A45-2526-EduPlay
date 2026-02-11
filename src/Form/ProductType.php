<?php

namespace App\Form;

use App\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du produit',
                'attr' => [
                    'class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full',
                    'placeholder' => 'Ex: Kit STEM Robot',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom du produit est obligatoire.']),
                    new Assert\Length(['min' => 2, 'max' => 255]),
                ],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix (€)',
                'attr' => [
                    'class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prix est obligatoire.']),
                    new Assert\Type(['type' => 'numeric', 'message' => 'Le prix doit être un nombre.']),
                    new Assert\PositiveOrZero(['message' => 'Le prix doit être positif ou nul.']),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => [
                    'class' => 'form-input rounded-lg border border-gray-300 px-4 py-2 w-full',
                    'rows' => 4,
                    'placeholder' => 'Décrivez le produit...',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'La description est obligatoire.']),
                    new Assert\Length(['min' => 5, 'max' => 255]),
                ],
            ])
            ->add('availability', CheckboxType::class, [
                'label' => 'Disponible',
                'required' => false,
                'attr' => ['class' => 'form-checkbox rounded text-primary'],
            ]);
            // Picture upload (unmapped)
            $builder->add('pictureFile', FileType::class, [
                'label' => 'Image du produit (optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'form-input'],
                'constraints' => [
                    new Assert\File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez téléverser une image au format JPEG, PNG, WEBP ou GIF.',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser {{ limit }}.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
