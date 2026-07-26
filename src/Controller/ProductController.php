<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\StockService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/products', name: 'app_product_')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(ProductRepository $productRepository, StockService $stockService): Response
    {
        $products = $productRepository->findAvailableSorted();

        return $this->render('product/index.html.twig', [
            'products' => $products,
            'stockMap' => $stockService->getAvailableMap($products),
        ]);
    }

    #[Route('/search', name: 'search')]
    public function search(Request $request, ProductRepository $productRepository): Response
    {
        $terme = $request->query->get('q', '');

        return $this->render('search/index.html.twig', [
            'products' => $productRepository->search($terme),
            'terme' => $terme,
        ]);
    }

    #[Route('/filter', name: 'filter')]
    public function filter(Request $request, ProductRepository $productRepository): Response
    {
        $maxPrice = $request->query->getInt('maxPrice', 100);

        return $this->render('filter/index.html.twig', [
            'products' => $productRepository->search('', $maxPrice),
            'maxPrice' => $maxPrice,
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'])]
    public function show(Product $product, StockService $stockService): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
            'available' => $stockService->getAvailable($product, $this->getUser()),
        ]);
    }

    #[Route('/{id}/panier', name: 'add_to_cart', requirements: ['id' => '\d+'])]
    public function addToCart(Product $product): Response
    {
        $this->addFlash('success', $product->getName() . ' ajouté au panier !');

        return $this->redirectToRoute('app_product_index');
    }
}
