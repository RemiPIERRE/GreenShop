<?php

namespace App\Controller;

use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Repository\CartItemRepository;
use App\Service\StockService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier', name: 'app_cart_')]
#[IsGranted('ROLE_USER')]
final class CartController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(Request $request, CartItemRepository $cartItemRepository): Response
    {
        $request->getSession()->remove('cart_skip_modal');

        $items = $cartItemRepository->findActiveForUser($this->getUser());

        $total = '0.00';
        foreach ($items as $item) {
            $line = bcmul($item->getProduct()->getPrice(), (string)$item->getQuantity(), 2);
            $total = bcadd($total, $line, 2);
        }

        return $this->render('cart/index.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(
        Product                $product,
        Request                $request,
        StockService           $stockService,
        CartItemRepository     $cartItemRepository,
        EntityManagerInterface $em,
    ): Response
    {
        if (!$this->isCsrfTokenValid('cart-add-' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        $quantity = max(1, $request->request->getInt('quantity', 1));

        $existing = $cartItemRepository->findOneActiveForUserAndProduct($user, $product);
        $alreadyReserved = $existing?->getQuantity() ?? 0;

        $available = $stockService->getAvailable($product, $user);

        if ($quantity + $alreadyReserved > $available) {
            $this->addFlash('error', 'Stock insuffisant pour cette quantité.');
            return $this->redirectToRoute('app_product_show', ['id' => $product->getId()]);
        }

        if ($existing !== null) {
            $existing->setQuantity($alreadyReserved + $quantity);
        } else {
            $expiresAt = $cartItemRepository->findCartExpiry($user)
                ?? new \DateTimeImmutable('+1 hour');

            $item = new CartItem();
            $item->setUser($user);
            $item->setProduct($product);
            $item->setQuantity($quantity);
            $item->setExpiresAt($expiresAt);
            $em->persist($item);
        }

        $em->flush();

        $session = $request->getSession();

        if ($session->get('cart_skip_modal') === true) {
            $this->addFlash('success', $product->getName() . ' ajouté au panier.');
        } else {
            $this->addFlash('cart_added', $product->getName());
        }

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/retirer/{id}', name: 'remove', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function remove(CartItem $cartItem, Request $request, EntityManagerInterface $em): Response
    {
        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart-remove-' . $cartItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_cart_index');
        }

        $em->remove($cartItem);
        $em->flush();

        $this->addFlash('success', 'Article retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/commander', name: 'checkout')]
    public function checkout(CartItemRepository $cartItemRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $items = $cartItemRepository->findActiveForUser($user);

        // Panier vide (ou entièrement expiré) : rien à payer.
        if (count($items) === 0) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        $total = '0.00';
        foreach ($items as $item) {
            $line = bcmul($item->getProduct()->getPrice(), (string)$item->getQuantity(), 2);
            $total = bcadd($total, $line, 2);
        }

        return $this->render('cart/checkout.html.twig', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    #[Route('/quantite/{id}', name: 'update_quantity', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateQuantity(CartItem $cartItem, Request $request, StockService $stockService, EntityManagerInterface $em): Response
    {
        if ($cartItem->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('cart-qty-' . $cartItem->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_cart_index');
        }

        $action = $request->request->get('action');
        $current = $cartItem->getQuantity();

        if ($action === 'inc') {
            $available = $stockService->getAvailable($cartItem->getProduct(), $this->getUser());

            if ($current + 1 > $available) {
                $this->addFlash('error', 'Stock insuffisant.');
                return $this->redirectToRoute('app_cart_index');
            }

            $cartItem->setQuantity($current + 1);
        } elseif ($action === 'dec') {
            if ($current <= 1) {
                $em->remove($cartItem);
                $em->flush();
                $this->addFlash('success', 'Article retiré du panier.');
                return $this->redirectToRoute('app_cart_index');
            }

            $cartItem->setQuantity($current - 1);
        }

        $em->flush();

        return $this->redirectToRoute('app_cart_index');
    }

    #[Route('/continuer', name: 'continue', methods: ['POST'])]
    public function continueShopping(Request $request): Response
    {
        $request->getSession()->set('cart_skip_modal', true);

        return $this->redirectToRoute('app_product_index');
    }

    #[Route('/payer', name: 'pay', methods: ['POST'])]
    public function pay(
        Request                $request,
        CartItemRepository     $cartItemRepository,
        EntityManagerInterface $em,
    ): Response
    {
        if (!$this->isCsrfTokenValid('checkout', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_cart_checkout');
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $items = $cartItemRepository->findActiveForUser($user);

        if (count($items) === 0) {
            $this->addFlash('error', 'Votre panier est vide.');
            return $this->redirectToRoute('app_cart_index');
        }

        $em->beginTransaction();

        try {
            $order = new Order();
            $order->setUser($user);
            $order->setStatus(OrderStatus::Preparing);
            $order->setCreatedAt(new \DateTimeImmutable());
            $order->setReference('CMD-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4))));

            $total = '0.00';

            foreach ($items as $cartItem) {
                $product = $cartItem->getProduct();
                $quantity = $cartItem->getQuantity();

                if ($product->getStock() < $quantity) {
                    throw new \RuntimeException(sprintf(
                        'Stock insuffisant pour %s (demandé %d, disponible %d).',
                        $product->getName(), $quantity, $product->getStock()
                    ));
                }

                $product->setStock($product->getStock() - $quantity);

                $orderItem = new OrderItem();
                $orderItem->setProduct($product);
                $orderItem->setQuantity($quantity);
                $orderItem->setUnitPrice($product->getPrice());
                $order->addItem($orderItem);

                $total = bcadd(
                    $total,
                    bcmul($product->getPrice(), (string)$quantity, 2),
                    2
                );

                $em->remove($cartItem);
            }

            $order->setTotal($total);
            $em->persist($order);

            $em->flush();
            $em->commit();

        } catch (\Throwable $e) {
            $em->rollback();

            $this->addFlash('error', 'La commande n\'a pas pu être validée : ' . $e->getMessage());
            return $this->redirectToRoute('app_cart_index');
        }

        $this->addFlash('success', 'Commande ' . $order->getReference() . ' confirmée. Merci !');

        return $this->redirectToRoute('app_account_order_show', ['id' => $order->getId()]);
    }
}
