<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BackOfficeController extends AbstractController
{
    #[Route('/back/office', name: 'app_back_office')]
    public function index(Request $request): Response
    {
        // TODO: Get from authentication
        $testRole = $request->query->get('role', 'admin');
        $userRole = 'ROLE_' . strtoupper($testRole);
        
        // Block parent/kid from accessing back office
        if (in_array($userRole, ['ROLE_PARENT', 'ROLE_KID'])) {
            return $this->redirectToRoute('app_course_index', ['role' => $testRole]);
        }
        
        return $this->render('back_office/index.html.twig', [
            'controller_name' => 'BackOfficeController',
        ]);
    }
}
