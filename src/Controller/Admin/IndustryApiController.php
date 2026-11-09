<?php

namespace App\Controller\Admin;

use App\Entity\Assortment;
use App\Entity\Industry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class IndustryApiController extends AbstractController
{
    #[Route('/admin/api/industry/create', name: 'admin_api_industry_create', methods: ['POST'])]
    public function createIndustry(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->createEntity($request, $em, Industry::class, 'Nazwa branży jest wymagana');
    }

    #[Route('/admin/api/assortment/create', name: 'admin_api_assortment_create', methods: ['POST'])]
    public function createAssortment(Request $request, EntityManagerInterface $em): JsonResponse
    {
        return $this->createEntity($request, $em, Assortment::class, 'Nazwa asortymentu jest wymagana');
    }

    private function createEntity(Request $request, EntityManagerInterface $em, string $class, string $errorMsg): JsonResponse
    {
        $name = trim($request->request->get('name', ''));

        if ($name === '') {
            return new JsonResponse(['error' => $errorMsg], 400);
        }

        $existing = $em->getRepository($class)->findOneBy(['name' => $name]);
        if ($existing) {
            return new JsonResponse([
                'entityId' => (string) $existing->getId(),
                'entityAsString' => $existing->getName(),
            ]);
        }

        $entity = new $class();
        $entity->setName($name);
        $em->persist($entity);
        $em->flush();

        return new JsonResponse([
            'entityId' => (string) $entity->getId(),
            'entityAsString' => $entity->getName(),
        ]);
    }
}

