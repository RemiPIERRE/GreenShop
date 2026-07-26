<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_product_')]
final class ProductApiController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ProductRepository $repo): JsonResponse
    {
        return $this->json($repo->findBy(['active' => true]), 200, [], [
            'groups' => ['product:read'],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, ProductRepository $repo): JsonResponse
    {
        $product = $repo->find($id);

        if ($product === null) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        return $this->json($product, 200, [], ['groups' => ['product:read']]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        Request                $request,
        SerializerInterface    $serializer,
        ValidatorInterface     $validator,
        EntityManagerInterface $em,
    ): JsonResponse
    {
        try {
            $product = $serializer->deserialize(
                $request->getContent(),
                Product::class,
                'json',
                ['groups' => ['product:write']]
            );
        } catch (\Throwable) {
            return $this->json(['error' => 'JSON invalide.'], 400);
        }

        // Champs que le client ne fournit pas
        $product->setCreatedAt(new \DateTimeImmutable());
        $product->setActive(true);
        $product->setSlug(uniqid('p-'));

        $errors = $validator->validate($product);

        if (count($errors) > 0) {
            $details = [];
            foreach ($errors as $error) {
                $details[$error->getPropertyPath()] = $error->getMessage();
            }

            return $this->json(['errors' => $details], 422);
        }

        $em->persist($product);
        $em->flush();

        return $this->json($product, 201, [], ['groups' => ['product:read']]);
    }

    #[Route('', name: 'delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(int $id, ProductRepository $repo, EntityManagerInterface $em): Response
    {
        $product = $repo->find($id);

        if ($product === null) {
            return $this->json(['error' => 'Produit introuvable.'], 404);
        }

        $em->remove($product);
        $em->flush();

        return new Response(null, 204);
    }
    
}
