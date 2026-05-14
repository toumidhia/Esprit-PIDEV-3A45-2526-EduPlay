<?php

namespace App\Form;

use App\Entity\EventRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $inputClass = "mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500";
        $labelClass = "text-sm font-extrabold text-gray-900";

        $builder
            ->add('childFullName', TextType::class, [
                'label' => "Child full name *",
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => "e.g. Lina Ben Ali",
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => "Child name is required."]),
                    new Assert\Length(['max' => 120]),
                ],
            ])
            ->add('parentPhone', TelType::class, [
                'label' => "Parent phone (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => "+216 XX XXX XXX",
                ],
            ])
            ->add('childClassLevel', TextType::class, [
                'label' => "Class level (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => "e.g. 3A / CE2",
                ],
            ])
            ->add('emergencyContactName', TextType::class, [
                'label' => "Emergency contact name (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => "Full name",
                ],
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => "Emergency contact phone (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => $inputClass,
                    'placeholder' => "+216 XX XXX XXX",
                ],
            ])
            ->add('medicalNotes', TextareaType::class, [
                'label' => "Medical notes (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => "mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
                    'rows' => 4,
                    'placeholder' => "Allergies, asthma, etc.",
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => "Additional notes (optional)",
                'required' => false,
                'label_attr' => ['class' => $labelClass],
                'attr' => [
                    'class' => "mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500",
                    'rows' => 4,
                    'placeholder' => "Any extra information for the school.",
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRegistration::class,
        ]);
    }
}