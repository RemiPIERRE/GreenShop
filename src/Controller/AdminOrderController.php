<?php

namespace App\Controller;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/commandes', name: 'app_admin_order_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminOrderController extends AbstractController
{
    #[Route('', name: 'index')]
    #[Route('', name: 'index')]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        $status = $request->query->get('status');
        $sort = $request->query->get('sort', 'createdAt');
        $dir = $request->query->get('dir', 'DESC');

        // Liste blanche : les données viennent de l'URL, donc du client.
        $allowed = ['reference', 'createdAt', 'total', 'status'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'createdAt';
        $dir = $dir === 'ASC' ? 'ASC' : 'DESC';

        $criteria = ['archivedAt' => null];
        $statusEnum = $status !== null ? OrderStatus::tryFrom($status) : null;

        if ($statusEnum !== null) {
            $criteria['status'] = $statusEnum;
        }

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orderRepository->findBy($criteria, [$sort => $dir]),
            'archivedOrders' => $orderRepository->findArchived(),
            'statuses' => OrderStatus::cases(),
            'currentStatus' => $status,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    #[Route('/{id}/archiver', name: 'archive', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function archive(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('archive-' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_order_index');
        }

        $order->setArchivedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Commande ' . $order->getReference() . ' archivée.');

        return $this->redirectToRoute('app_admin_order_index');
    }

    #[Route('/{id}/statut', name: 'status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('status-' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_admin_order_index');
        }

        $status = OrderStatus::tryFrom($request->request->get('status', ''));

        if ($status === null) {
            $this->addFlash('error', 'Statut invalide.');
            return $this->redirectToRoute('app_admin_order_index');
        }

        $order->setStatus($status);

        $em->flush();

        $this->addFlash('success', 'Commande ' . $order->getReference() . ' mise à jour !');

        return $this->redirectToRoute('app_admin_order_index');
    }
}
