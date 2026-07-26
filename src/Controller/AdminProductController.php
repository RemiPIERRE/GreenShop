<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/admin', name: 'app_admin_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/plants', name: 'plants')]
    public function plants(ProductRepository $productRepository): Response
    {
        return $this->render('admin/plants.html.twig', [
            'products' => $productRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $slugger = new AsciiSlugger();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $slugger, $product);

            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setSlug($slugger->slug($product->getName())->lower());

            $em->persist($product);
            $em->flush();

            $this->addFlash('success', $product->getName() . ' ajouté au catalogue !');

            return $this->redirectToRoute('app_admin_plants');
        }

        return $this->render('admin/product/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'product_edit', requirements: ['id' => '\d+'])]
    public function edit(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);
        $slugger = new AsciiSlugger();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $slugger, $product);

            $em->flush();

            $this->addFlash('success', $product->getName() . ' mis à jour.');

            return $this->redirectToRoute('app_admin_plants');
        }

        return $this->render('admin/product/edit.html.twig', [
            'form' => $form,
            'product' => $product,
        ]);
    }

    #[Route('/{id}/delete', name: 'product_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Product $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete-product-' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');

            return $this->redirectToRoute('app_admin_plants');
        }
        
        $orderItemCount = $em->getRepository(OrderItem::class)
            ->count(['product' => $product]);

        if ($orderItemCount > 0) {
            $product->setActive(false);
            $em->flush();

            $this->addFlash('warning',
                $product->getName() . ' a un historique de commandes : il a été retiré '
                . 'du catalogue (dépublié) plutôt que supprimé.');

            return $this->redirectToRoute('app_admin_plants');
        }

        foreach ($em->getRepository(CartItem::class)->findBy(['product' => $product]) as $cartItem) {
            $em->remove($cartItem);
        }

        if ($product->getImage()) {
            $imagePath = $this->getParameter('kernel.project_dir')
                . '/public/uploads/products/' . $product->getImage();

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        $name = $product->getName();

        $em->remove($product);
        $em->flush();

        $this->addFlash('success', $name . ' retiré du catalogue.');

        return $this->redirectToRoute('app_admin_plants');
    }

    private function handleImageUpload(FormInterface $form, AsciiSlugger $slugger, Product $product): void
    {
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile === null) {
            return;
        }

        $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = $slugger->slug($originalName)->lower();
        $newFilename = $safeName . '-' . uniqid() . '.' . $imageFile->guessExtension();

        $imageFile->move(
            $this->getParameter('kernel.project_dir') . '/public/uploads/products',
            $newFilename
        );

        $product->setImage($newFilename);
    }
}
