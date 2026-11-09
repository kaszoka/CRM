<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpFoundation\Request;

final class ClientEmailExporter
{

    public function export(QueryBuilder $qb, Request $request, int $pageSize): array
    {
        $pageSize = max(1, $pageSize);

        $totalClients = \count(new Paginator(
            (clone $qb)->resetDQLPart('orderBy')->getQuery(),
            true,
        ));
        $lastPage = max(1, (int) ceil($totalClients / $pageSize));
        $pages = $this->resolvePages($request, $lastPage);

        $clientsById = [];
        foreach ($pages as $page) {
            $offset = ($page - 1) * $pageSize;
            $pagePaginator = new Paginator(
                (clone $qb)
                    ->setFirstResult($offset)
                    ->setMaxResults($pageSize)
                    ->getQuery(),
                true,
            );

            foreach ($pagePaginator as $client) {
                if (!$client instanceof Client) {
                    continue;
                }

                $id = $client->getId();
                if ($id === null) {
                    continue;
                }

                $clientsById[$id] = $client;
            }
        }

        $result = [];
        foreach ($clientsById as $client) {
            $email = trim((string) ($client->getEmail() ?? ''));
            if ($email === '') {
                continue;
            }

            $result[] = [
                'id' => (int) $client->getId(),
                'email' => $email,
                'name' => (string) ($client->getCompanyName() ?? ''),
            ];
        }

        return [
            'clients' => $result,
            'pages' => $pages,
            'lastPage' => $lastPage,
            'count' => \count($result),
        ];
    }

    private function resolvePages(Request $request, int $lastPage): array
    {
        if ($request->query->getBoolean('all')) {
            return range(1, $lastPage);
        }

        $pages = $request->query->all('pages');
        if ($pages !== []) {
            $normalized = [];
            foreach ((array) $pages as $page) {
                $pageNumber = (int) $page;
                if ($pageNumber >= 1 && $pageNumber <= $lastPage) {
                    $normalized[$pageNumber] = $pageNumber;
                }
            }
            if ($normalized !== []) {
                ksort($normalized);

                return array_values($normalized);
            }
        }

        $pageFrom = max(1, (int) $request->query->get('page_from', 1));
        $pageTo = max($pageFrom, (int) $request->query->get('page_to', $pageFrom));
        $pageFrom = min($pageFrom, $lastPage);
        $pageTo = min($pageTo, $lastPage);

        return range($pageFrom, $pageTo);
    }
}

