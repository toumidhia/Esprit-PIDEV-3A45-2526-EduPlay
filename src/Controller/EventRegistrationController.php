<?php

namespace App\Controller;

use App\Entity\EventRegistration;
use App\Entity\SchoolEvent;
use App\Entity\User;
use App\Form\EventRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class EventRegistrationController extends AbstractController
{
    #[Route('/events/{id}/register', name: 'front_event_register', methods: ['GET','POST'])]
    public function register(SchoolEvent $event, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // ✅ Parent uniquement (fonctionne avec in-memory roles aussi)
        if (!$this->isGranted('ROLE_PARENT')) {
            throw $this->createAccessDeniedException("Seuls les parents peuvent inscrire un enfant.");
        }

        // ✅ On doit absolument avoir un App\Entity\User pour persister (relation ManyToOne)
        $parentEntity = $this->getOrCreateParentEntity($em);

        $registration = new EventRegistration();
        $registration->setEvent($event);
        $registration->setParent($parentEntity);
        $registration->setRegisteredAt(new \DateTimeImmutable());
        $registration->setStatus(EventRegistration::STATUS_PENDING);

        $form = $this->createForm(EventRegistrationType::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $child = trim((string) $registration->getChildFullName());

            if ($child === '') {
                $form->addError(new FormError("Le nom de l'enfant est obligatoire."));
            } else {
                // ✅ doublon strict (même parent + même enfant + même event)
                $exists = $em->getRepository(EventRegistration::class)->findOneBy([
                    'event' => $event,
                    'parent' => $parentEntity,
                    'childFullName' => $child,
                ]);

                if ($exists) {
                    $form->addError(new FormError("Cet enfant est déjà inscrit à cet événement."));
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($registration);
            $em->flush();

            $this->addFlash('success', "Inscription enregistrée ✅");

            // ✅ Redirection demandée : vers la liste "Mes inscriptions"
            return $this->redirectToRoute('front_my_registrations');
        }

        // ✅ Ton chemin réel (respecte exactement la casse de ton dossier)
        return $this->render('FrontOffice/Parent/event/registration/new.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/my-registrations', name: 'front_my_registrations', methods: ['GET'])]
    public function myRegistrations(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isGranted('ROLE_PARENT')) {
            throw $this->createAccessDeniedException("Seuls les parents peuvent accéder aux inscriptions.");
        }

        $parentEntity = $this->getOrCreateParentEntity($em);

        $registrations = $em->getRepository(EventRegistration::class)->findBy(
            ['parent' => $parentEntity],
            ['registeredAt' => 'DESC']
        );

        return $this->render('FrontOffice/Parent/event/registration/index.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    #[Route('/registrations/{id}/cancel', name: 'front_registration_cancel', methods: ['POST'])]
    public function cancel(EventRegistration $registration, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $parentEntity = $this->getOrCreateParentEntity($em);

        if ($registration->getParent()?->getId() !== $parentEntity->getId()) {
            throw $this->createAccessDeniedException("Action non autorisée.");
        }

        if ($this->isCsrfTokenValid('cancel_registration_' . $registration->getId(), $request->request->get('_token'))) {
            $em->remove($registration);
            $em->flush();
            $this->addFlash('success', 'Inscription annulée ✅');
        }

        return $this->redirectToRoute('front_my_registrations');
    }

    /**
     * ✅ Permet de tester maintenant avec users_in_memory:
     * - si user = App\Entity\User => OK
     * - sinon (InMemoryUser) => créer/récupérer un User DB "fake" correspondant
     */
    private function getOrCreateParentEntity(EntityManagerInterface $em): User
    {
        $u = $this->getUser();

        if ($u instanceof User) {
            return $u;
        }

        if (!$u instanceof UserInterface) {
            throw $this->createAccessDeniedException('Utilisateur invalide.');
        }

        $identifier = $u->getUserIdentifier(); // ex: parent_test
        $email = str_contains($identifier, '@') ? $identifier : ($identifier . '@test.local');

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing instanceof User) {
            return $existing;
        }

        $fake = new User();
        $fake->setFirstName('Parent');
        $fake->setLastName('Test');
        $fake->setEmail($email);
        $fake->setPassword('test'); // pas utilisé ici (http_basic in-memory gère l'auth)
        $fake->setType('PARENT');
        $fake->setRole(['ROLE_PARENT']);
        $fake->setActive(true);
        $fake->setBirthDate(new \DateTime('2000-01-01'));

        $em->persist($fake);
        $em->flush();

        return $fake;
    }
}
