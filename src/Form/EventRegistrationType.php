<?php

namespace App\Form;

use App\Entity\EventRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('childFullName', TextType::class, [
            'label' => "Nom complet de l'enfant",
            'attr' => [
                'placeholder' => "Ex: Lina Ben Ali",
            ],
            'constraints' => [
                new Assert\NotBlank(['message' => "Le nom de l'enfant est obligatoire."]),
                new Assert\Length(['max' => 120]),
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
