<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TasteController extends AbstractController
{
    #[Route('/taste', name: 'app_taste')]
    public function index(): Response
    {
        return $this->render('taste/index.html.twig', [
            'controller_name' => 'TasteController',
        ]);
    }
}
