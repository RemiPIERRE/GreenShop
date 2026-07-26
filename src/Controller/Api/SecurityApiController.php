<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

final class SecurityApiController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException(
            'Cette méthode est interceptée par le firewall json_login. '
            . 'Si vous voyez ce message, vérifiez la configuration de security.yaml.'
        );
    }
}
