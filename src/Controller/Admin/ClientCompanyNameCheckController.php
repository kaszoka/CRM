<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\ClientRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ClientCompanyNameCheckController extends AbstractController
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    #[Route('/admin/client/company-name-check', name: 'admin_client_company_name_check', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $name = trim((string) $request->query->get('name', ''));
        $excludeId = (int) $request->query->get('excludeId', 0);

        if ($name === '') {
            return $this->json([
                'exact' => null,
                'similar' => [],
            ]);
        }

        $needle = $this->normalizeCompanyName($name);
        $needleComparable = $this->normalizeForSimilarity($name);
        $similar = [];
        $exact = null;

        $rows = $this->clientRepository->createQueryBuilder('c')
            ->select('c.id AS id, c.companyName AS companyName, c.nip AS nip')
            ->andWhere('c.deletedAt IS NULL')
            ->orderBy('c.companyName', 'ASC')
            ->getQuery()
            ->getArrayResult();

        foreach ($rows as $row) {
            $id = (int) $row['id'];
            if ($excludeId > 0 && $id === $excludeId) {
                continue;
            }

            $companyName = (string) ($row['companyName'] ?? '');
            if ($companyName === '') {
                continue;
            }

            if ($this->normalizeCompanyName($companyName) === $needle) {
                $exact = $this->formatClientResult($id, $companyName, $row['nip'] ?? null, 100);
                break;
            }

            $candidateComparable = $this->normalizeForSimilarity($companyName);
            if ($needleComparable === '' || $candidateComparable === '') {
                continue;
            }

            similar_text($needleComparable, $candidateComparable, $percent);
            $contains = mb_strlen($needleComparable) >= 4
                && (str_contains($candidateComparable, $needleComparable) || str_contains($needleComparable, $candidateComparable));

            if ($percent >= 75.0 || $contains) {
                $similar[] = $this->formatClientResult($id, $companyName, $row['nip'] ?? null, (int) round($percent));
            }
        }

        usort($similar, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return $this->json([
            'exact' => $exact,
            'similar' => array_slice($similar, 0, 5),
        ]);
    }

    private function normalizeCompanyName(string $name): string
    {
        $name = mb_strtolower(trim($name), 'UTF-8');

        return (string) preg_replace('/\s+/u', ' ', $name);
    }

    private function normalizeForSimilarity(string $name): string
    {
        $name = $this->normalizeCompanyName($name);
        $name = (string) preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name);

        return (string) preg_replace('/\s+/u', ' ', trim($name));
    }

    private function formatClientResult(int $id, string $companyName, mixed $nip, int $score): array
    {
        return [
            'id' => $id,
            'companyName' => $companyName,
            'nip' => $nip ?: null,
            'score' => $score,
            'detailUrl' => $this->generateClientUrl($id, Action::DETAIL),
            'editUrl' => $this->generateClientUrl($id, Action::EDIT),
        ];
    }

    private function generateClientUrl(int $id, string $action): string
    {
        return $this->adminUrlGenerator
            ->unsetAll()
            ->setController(ClientCrudController::class)
            ->setAction($action)
            ->setEntityId($id)
            ->generateUrl();
    }
}

