<?php

namespace App\Controller\Admin;

use App\Entity\ActivityLog;
use App\Entity\Task;
use App\Entity\TaskNote;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/task-action')]
#[IsGranted('ROLE_USER')]
class TaskActionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ActivityLogger $activityLogger,
    ) {
    }

    #[Route('/change-status/{id}', name: 'task_change_status', methods: ['POST'])]
    public function changeStatus(Task $task, Request $request): Response
    {
        $newStatus = $request->request->get('status');
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('task_status_' . $task->getId(), $token)) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToReferer($request, $task);
        }

        $validStatuses = [Task::STATUS_NEW, Task::STATUS_IN_PROGRESS, Task::STATUS_COMPLETED];
        if (!in_array($newStatus, $validStatuses, true)) {
            $this->addFlash('danger', 'Nieprawidłowy status.');
            return $this->redirectToReferer($request, $task);
        }

        $oldStatus = $task->getStatus();
        $oldStatusLabel = $task->getStatusLabel();
        $task->setStatus($newStatus);
        $this->em->flush();

        $statusLabels = array_flip(Task::STATUSES);
        $newStatusLabel = $statusLabels[$newStatus] ?? $newStatus;

        $this->activityLogger->log(
            action: ActivityLog::ACTION_STATUS_CHANGE,
            entityType: 'Task',
            entityId: $task->getId(),
            entityName: $task->getTitle(),
            changes: ['status' => ['old' => $oldStatusLabel, 'new' => $newStatusLabel]],
            description: 'Status: ' . $oldStatusLabel . ' → ' . $newStatusLabel,
        );

        $this->addFlash('success', 'Status zmieniony na: ' . $newStatusLabel);

        return $this->redirectToReferer($request, $task);
    }

    #[Route('/add-note/{id}', name: 'task_add_note', methods: ['POST'])]
    public function addNote(Task $task, Request $request): Response
    {
        $content = trim($request->request->get('content', ''));
        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('task_note_' . $task->getId(), $token)) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToReferer($request, $task);
        }

        if ($content === '') {
            $this->addFlash('warning', 'Treść notatki nie może być pusta.');
            return $this->redirectToReferer($request, $task);
        }

        $note = new TaskNote();
        $note->setTask($task);
        $note->setContent($content);
        $note->setAuthor($this->getUser());

        $this->em->persist($note);
        $this->em->flush();

        $this->activityLogger->log(
            action: ActivityLog::ACTION_NOTE_ADD,
            entityType: 'Task',
            entityId: $task->getId(),
            entityName: $task->getTitle(),
            description: 'Dodano notatkę: ' . mb_substr($content, 0, 80) . (mb_strlen($content) > 80 ? '...' : ''),
        );

        $this->addFlash('success', 'Notatka została dodana.');

        return $this->redirectToReferer($request, $task);
    }

    #[Route('/delete-note/{id}', name: 'task_delete_note', methods: ['POST'])]
    public function deleteNote(TaskNote $note, Request $request): Response
    {
        $token = $request->request->get('_token');
        $task = $note->getTask();

        if (!$this->isCsrfTokenValid('delete_note_' . $note->getId(), $token)) {
            $this->addFlash('danger', 'Nieprawidłowy token bezpieczeństwa.');
            return $this->redirectToReferer($request, $task);
        }

        $noteContent = mb_substr($note->getContent(), 0, 80);
        $this->em->remove($note);
        $this->em->flush();

        $this->activityLogger->log(
            action: ActivityLog::ACTION_NOTE_DELETE,
            entityType: 'Task',
            entityId: $task->getId(),
            entityName: $task->getTitle(),
            description: 'Usunięto notatkę: ' . $noteContent,
        );

        $this->addFlash('success', 'Notatka została usunięta.');

        return $this->redirectToReferer($request, $task);
    }

    private function redirectToReferer(Request $request, ?Task $task): Response
    {
        $referer = $request->headers->get('referer');
        if ($referer) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('admin');
    }
}

