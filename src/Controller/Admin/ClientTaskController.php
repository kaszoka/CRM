<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\Task;
use App\Entity\User;
use App\Form\QuickCompletedTaskType;
use App\Form\QuickTaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ClientTaskController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Security $security,
    ) {
    }

    #[Route('/admin/client/{id}/add-task', name: 'admin_client_add_task', methods: ['POST'])]
    public function addTask(Client $client, Request $request): Response
    {

        $this->updateClientFromRequest($client, $request);

        $task = new Task();
        $task->setClient($client);
        $task->setCreatedBy($this->security->getUser());

        $taskForm = $this->createForm(QuickTaskType::class, $task);
        $taskForm->handleRequest($request);

        if ($taskForm->isSubmitted() && $taskForm->isValid()) {
            $this->em->persist($task);
            $this->em->flush();
            $this->addFlash('success', 'Zadanie zostało dodane.');
        } else {
            $this->em->flush();
            $errors = [];
            foreach ($taskForm->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', 'Nie udało się zapisać zadania: ' . implode(', ', $errors));
        }

        return $this->redirectToRoute('admin_client_edit', ['entityId' => $client->getId()]);
    }

    #[Route('/admin/client/{id}/add-completed-task', name: 'admin_client_add_completed_task', methods: ['POST'])]
    public function addCompletedTask(Client $client, Request $request): Response
    {
        $this->updateClientFromRequest($client, $request);

        $task = new Task();
        $task->setClient($client);
        $actor = $this->security->getUser();
        $task->setCreatedBy($actor instanceof User ? $actor : null);
        if ($actor instanceof User) {
            $task->setAssignedTo($actor);
        }
        $task->setStatus(Task::STATUS_COMPLETED);
        $task->setPriority(Task::PRIORITY_LOW);

        $form = $this->createForm(QuickCompletedTaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($task);
            $this->em->flush();
            $this->addFlash('success', 'Zadanie zakończone zostało dodane.');
        } else {
            $this->em->flush();
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('danger', 'Nie udało się zapisać zadania: ' . implode(', ', $errors));
        }

        return $this->redirectToRoute('admin_client_edit', ['entityId' => $client->getId()]);
    }

    private function updateClientFromRequest(Client $client, Request $request): void
    {
        $data = $request->request->all('Client');
        if (empty($data)) {
            return;
        }

        if (isset($data['companyName']) && $data['companyName'] !== '') {
            $client->setCompanyName($data['companyName']);
        }
        if (array_key_exists('nip', $data)) {
            $client->setNip($data['nip'] !== '' ? $data['nip'] : null);
        }
        if (array_key_exists('phone', $data)) {
            $client->setPhone($data['phone'] !== '' ? $data['phone'] : null);
        }
        if (array_key_exists('email', $data)) {
            $client->setEmail($data['email'] !== '' ? $data['email'] : null);
        }
        if (array_key_exists('street', $data)) {
            $client->setStreet($data['street'] !== '' ? $data['street'] : null);
        }
        if (array_key_exists('city', $data)) {
            $client->setCity($data['city'] !== '' ? $data['city'] : null);
        }
        if (array_key_exists('postalCode', $data)) {
            $client->setPostalCode($data['postalCode'] !== '' ? $data['postalCode'] : null);
        }
        if (array_key_exists('notes', $data)) {
            $client->setNotes($data['notes'] !== '' ? $data['notes'] : null);
        }
        if (array_key_exists('turnover', $data)) {
            $client->setTurnover($data['turnover'] !== '' ? $data['turnover'] : null);
        }
        if (array_key_exists('hasBusinessAccount', $data)) {
            $client->setHasBusinessAccount((bool) $data['hasBusinessAccount']);
        } else {
            $client->setHasBusinessAccount(false);
        }
        if (array_key_exists('hasPurchased', $data)) {
            $client->setHasPurchased((bool) $data['hasPurchased']);
        } else {
            $client->setHasPurchased(false);
        }
        if (array_key_exists('contactMethods', $data)) {
            $methodIds = is_array($data['contactMethods']) ? $data['contactMethods'] : [];
            $repo = $this->em->getRepository(\App\Entity\ContactMethod::class);

            foreach ($client->getContactMethods()->toArray() as $existing) {
                $client->removeContactMethod($existing);
            }
            foreach ($methodIds as $methodId) {
                $method = $repo->find((int) $methodId);
                if ($method) {
                    $client->addContactMethod($method);
                }
            }
        }
    }
}

