<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Profile;
use App\Form\ProfileType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/compte', name: 'app_account_')]
#[IsGranted('ROLE_USER')]
final class AccountController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(OrderRepository $orderRepository): Response
    {
        $user = $this->getUser();
        
        if ($user->getProfile() === null) {
            return $this->redirectToRoute('app_account_profile');
        }

        $orders = $orderRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        $totalSpent = '0.00';
        $plantCount = 0;

        foreach ($orders as $order) {
            $totalSpent = bcadd($totalSpent, $order->getTotal(), 2);

            foreach ($order->getItems() as $item) {
                $plantCount += $item->getQuantity();
            }
        }

        return $this->render('account/index.html.twig', [
            'orderCount' => count($orders),
            'totalSpent' => $totalSpent,
            'plantCount' => $plantCount,
            'lastOrder' => $orders[0] ?? null,
        ]);
    }

    #[Route('/profile', name: 'profile')]
    public function profile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $profile = $user->getProfile() ?? new Profile();
        $profile->setUser($user);

        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($profile);
            $em->flush();

            $this->addFlash('success', 'Profil enregistré.');

            return $this->redirectToRoute('app_account_index');
        }

        return $this->render('account/profile.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/commandes', name: 'orders')]
    public function orders(OrderRepository $orderRepository): Response
    {
        return $this->render('account/orders.html.twig', [
            'orders' => $orderRepository->findBy(
                ['user' => $this->getUser()],
                ['createdAt' => 'DESC']
            ),
        ]);
    }

    #[Route('/commandes/{id}', name: 'order_show', requirements: ['id' => '\d+'])]
    #[IsGranted('VIEW', subject: 'order')]
    public function orderShow(Order $order): Response
    {
        return $this->render('account/order_show.html.twig', [
            'order' => $order,
        ]);
    }
}
