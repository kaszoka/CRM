<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ContactMethod;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

final class ClientListQueryFilter
{
    public function apply(QueryBuilder $qb, Request $request): void
    {
        $qb->andWhere('entity.deletedAt IS NULL');
        $qb->leftJoin('entity.createdBy', 'createdBy');

        $business = $request->query->get('f_business');
        $purchased = $request->query->get('f_purchased');
        $local = $request->query->get('f_local');
        $noNip = $request->query->get('f_no_nip');
        $industryName = $request->query->get('f_industry');
        $assortmentName = $request->query->get('f_assortment');
        $sourceName = $request->query->get('f_source');
        $createdBy = $request->query->get('f_created_by');
        $excludeNoContact = $request->query->get('f_exclude_no_contact');
        $excludeNotInterested = $request->query->get('f_exclude_not_interested');
        $excludeDetaliczny = $request->query->get('f_exclude_detaliczny');
        $turnoverSort = $request->query->get('f_sort_turnover');

        if ($business === '1' || $business === '0') {
            $qb->andWhere('entity.hasBusinessAccount = :hasBusiness')
                ->setParameter('hasBusiness', $business === '1');
        }

        if ($purchased === '1' || $purchased === '0') {
            $qb->andWhere('entity.hasPurchased = :hasPurchased')
                ->setParameter('hasPurchased', $purchased === '1');
        }

        if ($local === '1' || $local === '0') {
            $qb->andWhere('entity.isLocalClient = :isLocal')
                ->setParameter('isLocal', $local === '1');
        }

        if ($noNip === '1') {
            $qb->andWhere('entity.nip IS NULL OR entity.nip = :emptyNip')
                ->setParameter('emptyNip', '');
        }

        if ($industryName !== null && $industryName !== '') {
            $qb->leftJoin('entity.industries', 'fi')
                ->andWhere('fi.name = :industryName')
                ->setParameter('industryName', $industryName);
        }

        if ($assortmentName !== null && $assortmentName !== '') {
            $qb->leftJoin('entity.assortments', 'fa')
                ->andWhere('fa.name = :assortmentName')
                ->setParameter('assortmentName', $assortmentName);
        }

        if ($sourceName !== null && $sourceName !== '') {
            $qb->leftJoin('entity.sources', 'fs')
                ->andWhere('fs.name = :sourceName')
                ->setParameter('sourceName', $sourceName);
        }

        if ($createdBy !== null && $createdBy !== '' && ctype_digit((string) $createdBy)) {
            $qb->andWhere('createdBy.id = :createdById')
                ->setParameter('createdById', (int) $createdBy);
        }

        if ($excludeNoContact !== '0') {
            $qb->leftJoin('entity.contactMethods', 'excludeNoContactMethod', 'WITH', 'excludeNoContactMethod.slug = :excludeNoContactSlug')
                ->andWhere('excludeNoContactMethod.id IS NULL')
                ->setParameter('excludeNoContactSlug', ContactMethod::SLUG_DO_NOT_CONTACT);
        }

        if ($excludeNotInterested !== '0') {
            $qb->leftJoin('entity.contactMethods', 'excludeNotInterestedMethod', 'WITH', 'excludeNotInterestedMethod.slug = :excludeNotInterestedSlug')
                ->andWhere('excludeNotInterestedMethod.id IS NULL')
                ->setParameter('excludeNotInterestedSlug', ContactMethod::SLUG_NOT_INTERESTED);
        }

        if ($excludeDetaliczny !== '0') {
            $qb->leftJoin('entity.contactMethods', 'excludeDetalicznyMethod', 'WITH', 'excludeDetalicznyMethod.slug = :excludeDetalicznySlug')
                ->andWhere('excludeDetalicznyMethod.id IS NULL')
                ->setParameter('excludeDetalicznySlug', ContactMethod::SLUG_RETAIL);
        }

        $searchQuery = trim((string) $request->query->get('query', ''));
        if ($searchQuery !== '') {
            $qb->andWhere(
                'entity.nip LIKE :searchQuery OR entity.companyName LIKE :searchQuery OR entity.phone LIKE :searchQuery OR entity.email LIKE :searchQuery OR entity.street LIKE :searchQuery OR entity.city LIKE :searchQuery OR entity.postalCode LIKE :searchQuery OR entity.notes LIKE :searchQuery'
            )->setParameter('searchQuery', '%'.$searchQuery.'%');
        }

        if ($turnoverSort !== null && $turnoverSort !== '') {
            $direction = strtolower((string) $turnoverSort) === 'asc' ? 'ASC' : 'DESC';
            $qb->resetDQLPart('orderBy');
            $qb->addOrderBy('CASE WHEN entity.turnover IS NULL THEN 0 ELSE entity.turnover END', $direction);
            $qb->addOrderBy('entity.createdAt', 'DESC');
        } elseif (empty($qb->getDQLPart('orderBy'))) {
            $qb->addOrderBy('entity.createdAt', 'DESC');
        }
    }
}

