<?php
// src/Form/EventRegistrationType.php

namespace App\Form;

use App\Entity\EventRegistration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<EventRegistration>
 */
class EventRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('childFullName', TextType::class, [
                'label' => "Nom complet de l'enfant",
                'attr' => ['placeholder' => "Ex: Lina Ben Ali"],
                'constraints' => [
                    new Assert\NotBlank(['message' => "Le nom de l'enfant est obligatoire."]),
                    new Assert\Length(['min' => 3, 'max' => 120]),
                ],
            ])
            ->add('parentPhone', TelType::class, [
                'label' => "Téléphone du parent",
                'required' => false,
                'attr' => ['placeholder' => "Ex: 22123456 ou +21622123456"],
                'constraints' => [
                    new Assert\Length(['max' => 30]),
                    new Assert\Regex([
                        'pattern' => '/^(\+?\d{1,3})?\d{8,12}$/',
                        'message' => "Téléphone invalide. Exemple: 22123456 ou +21622123456",
                    ]),
                ],
            ])
            ->add('childClassLevel', TextType::class, [
                'label' => "Classe / Niveau",
                'required' => false,
                'attr' => ['placeholder' => "Ex: 3A, CE2, 6ème..."],
                'constraints' => [
                    new Assert\Length(['max' => 80]),
                ],
            ])
            ->add('medicalNotes', TextareaType::class, [
                'label' => "Infos médicales (optionnel)",
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => "Allergies, asthme, médicaments..."],
                'constraints' => [
                    new Assert\Length(['max' => 2000]),
                ],
            ])
            ->add('emergencyContactName', TextType::class, [
                'label' => "Contact d'urgence - Nom",
                'required' => false,
                'attr' => ['placeholder' => "Ex: Tonton Ahmed"],
                'constraints' => [
                    new Assert\Length(['max' => 120]),
                ],
            ])
            ->add('emergencyContactPhone', TelType::class, [
                'label' => "Contact d'urgence - Téléphone",
                'required' => false,
                'attr' => ['placeholder' => "Ex: 55123456 ou +21655123456"],
                'constraints' => [
                    new Assert\Length(['max' => 30]),
                    new Assert\Regex([
                        'pattern' => '/^(\+?\d{1,3})?\d{8,12}$/',
                        'message' => "Téléphone invalide. Exemple: 55123456 ou +21655123456",
                    ]),
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => "Remarques (optionnel)",
                'required' => false,
                'attr' => ['rows' => 3, 'placeholder' => "Infos utiles, autorisations..."],
                'constraints' => [
                    new Assert\Length(['max' => 2000]),
                ],
            ])
        ;

        // ✅ Règles “intelligentes” : urgence (nom <-> téléphone)
        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            /** @var EventRegistration $data */
            $data = $event->getData();

            $name = trim((string) $data->getEmergencyContactName());
            $phone = trim((string) $data->getEmergencyContactPhone());

            if ($name !== '' && $phone === '') {
                $form->get('emergencyContactPhone')->addError(new FormError("Le téléphone du contact d'urgence est obligatoire."));
            }
            if ($phone !== '' && $name === '') {
                $form->get('emergencyContactName')->addError(new FormError("Le nom du contact d'urgence est obligatoire."));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventRegistration::class,
        ]);
    }
}