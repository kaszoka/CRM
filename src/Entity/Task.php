<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TaskRepository::class)]
#[ORM\Table(name: 'task')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['priority'], name: 'idx_task_priority')]
#[ORM\Index(columns: ['status'], name: 'idx_task_status')]
#[ORM\Index(columns: ['due_date'], name: 'idx_task_due_date')]
class Task
{
    public const PRIORITY_HIGH = 'wysoki';
    public const PRIORITY_MEDIUM = 'średni';
    public const PRIORITY_LOW = 'niski';

    public const STATUS_NEW = 'nowe';
    public const STATUS_IN_PROGRESS = 'w_trakcie';
    public const STATUS_COMPLETED = 'zakończone';

    public const PRIORITIES = [
        'Wysoki' => self::PRIORITY_HIGH,
        'Średni' => self::PRIORITY_MEDIUM,
        'Niski' => self::PRIORITY_LOW,
    ];

    public const STATUSES = [
        'Nowe' => self::STATUS_NEW,
        'W trakcie' => self::STATUS_IN_PROGRESS,
        'Zakończone' => self::STATUS_COMPLETED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Tytuł zadania jest wymagany.')]
    #[Assert\Length(max: 255, maxMessage: 'Tytuł nie może przekraczać {{ limit }} znaków.')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Priorytet jest wymagany.')]
    #[Assert\Choice(
        choices: [self::PRIORITY_HIGH, self::PRIORITY_MEDIUM, self::PRIORITY_LOW],
        message: 'Wybierz prawidłowy priorytet.'
    )]
    private ?string $priority = self::PRIORITY_MEDIUM;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: 'Status jest wymagany.')]
    #[Assert\Choice(
        choices: [self::STATUS_NEW, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED],
        message: 'Wybierz prawidłowy status.'
    )]
    private ?string $status = self::STATUS_NEW;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $assignedTo = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(targetEntity: TaskNote::class, mappedBy: 'task', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $notes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->notes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->title ?? 'Nowe zadanie';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): static
    {
        $this->assignedTo = $assignedTo;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(?\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getPriorityLabel(): string
    {
        return array_search($this->priority, self::PRIORITIES) ?: $this->priority;
    }

    public function getStatusLabel(): string
    {
        return array_search($this->status, self::STATUSES) ?: $this->status;
    }

    public function getNotes(): Collection
    {
        return $this->notes;
    }

    public function addNote(TaskNote $note): static
    {
        if (!$this->notes->contains($note)) {
            $this->notes->add($note);
            $note->setTask($this);
        }
        return $this;
    }

    public function removeNote(TaskNote $note): static
    {
        if ($this->notes->removeElement($note)) {
            if ($note->getTask() === $this) {
                $note->setTask(null);
            }
        }
        return $this;
    }
}

