<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['nip'], name: 'idx_client_nip')]
#[ORM\Index(columns: ['company_name'], name: 'idx_client_company_name')]
#[ORM\Index(columns: ['has_purchased'], name: 'idx_client_has_purchased')]
#[ORM\Index(columns: ['is_local_client'], name: 'idx_client_is_local')]
#[UniqueEntity(fields: ['nip'], message: 'Firma z tym NIP-em już istnieje w bazie.', ignoreNull: true)]
#[UniqueEntity(fields: ['companyName'], message: 'Firma o takiej nazwie już istnieje w bazie.')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20, unique: true, nullable: true)]
    private ?string $nip = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Nazwa firmy jest wymagana.')]
    #[Assert\Length(max: 255, maxMessage: 'Nazwa firmy nie może przekraczać {{ limit }} znaków.')]
    private ?string $companyName = null;

    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[\+]?[\d\s\-\(\)]{7,20}$/',
        message: 'Podaj prawidłowy numer telefonu.'
    )]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    #[Assert\Email(message: 'Podaj prawidłowy adres e-mail.')]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $street = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $city = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Regex(
        pattern: '/^\d{2}-\d{3}$/',
        message: 'Kod pocztowy musi być w formacie XX-XXX.'
    )]
    private ?string $postalCode = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasBusinessAccount = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasPurchased = false;

    #[ORM\ManyToMany(targetEntity: Industry::class, inversedBy: 'clients', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'client_industry')]
    private Collection $industries;

    #[ORM\ManyToMany(targetEntity: Assortment::class, inversedBy: 'clients', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'client_assortment')]
    private Collection $assortments;

    #[ORM\ManyToMany(targetEntity: ContactMethod::class, inversedBy: 'clients', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'client_contact_method')]
    private Collection $contactMethods;

    #[ORM\ManyToMany(targetEntity: Source::class, inversedBy: 'clients', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'client_source')]
    private Collection $sources;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isLocalClient = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero(message: 'Obroty muszą być wartością nieujemną.')]
    private ?string $turnover = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'client', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $tasks;

    #[ORM\OneToMany(targetEntity: Suggestion::class, mappedBy: 'client', cascade: ['remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'DESC'])]
    private Collection $suggestions;

    private ?string $newSuggestionTitle = null;

    private ?string $newSuggestionText = null;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'clientDrafts', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'client_suggestion_draft_tag')]
    private Collection $suggestionDraftTags;

    public function __construct()
    {
        $this->industries = new ArrayCollection();
        $this->assortments = new ArrayCollection();
        $this->sources = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->suggestions = new ArrayCollection();
        $this->suggestionDraftTags = new ArrayCollection();
        $this->contactMethods = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $nipStr = $this->nip ?? 'Działalność nierejestrowana';
        return $this->companyName . ' (NIP: ' . $nipStr . ')';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNip(): ?string
    {
        return $this->nip;
    }

    public function setNip(?string $nip): static
    {
        $this->nip = ($nip === '' || $nip === null) ? null : $nip;

        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(?string $street): static
    {
        $this->street = $street;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getFullAddress(): string
    {
        $parts = [];
        if ($this->street) {
            $parts[] = $this->street;
        }
        $cityPart = trim(($this->postalCode ?? '') . ' ' . ($this->city ?? ''));
        if ($cityPart) {
            $parts[] = $cityPart;
        }
        return implode(', ', $parts) ?: '';
    }

    public function isHasBusinessAccount(): bool
    {
        return $this->hasBusinessAccount;
    }

    public function setHasBusinessAccount(bool $hasBusinessAccount): static
    {
        $this->hasBusinessAccount = $hasBusinessAccount;

        return $this;
    }

    public function isHasPurchased(): bool
    {
        return $this->hasPurchased;
    }

    public function setHasPurchased(bool $hasPurchased): static
    {
        $this->hasPurchased = $hasPurchased;

        return $this;
    }

    public function getIndustries(): Collection
    {
        return $this->industries;
    }

    public function addIndustry(Industry $industry): static
    {
        if (!$this->industries->contains($industry)) {
            $this->industries->add($industry);
        }

        return $this;
    }

    public function removeIndustry(Industry $industry): static
    {
        $this->industries->removeElement($industry);

        return $this;
    }

    public function getIndustriesString(): string
    {
        return implode(', ', $this->industries->map(fn(Industry $i) => $i->getName())->toArray());
    }

    public function getAssortments(): Collection
    {
        return $this->assortments;
    }

    public function addAssortment(Assortment $assortment): static
    {
        if (!$this->assortments->contains($assortment)) {
            $this->assortments->add($assortment);
        }
        return $this;
    }

    public function removeAssortment(Assortment $assortment): static
    {
        $this->assortments->removeElement($assortment);
        return $this;
    }

    public function getAssortmentsString(): string
    {
        return implode(', ', $this->assortments->map(fn(Assortment $a) => $a->getName())->toArray());
    }

    public function getSources(): Collection
    {
        return $this->sources;
    }

    public function addSource(Source $source): static
    {
        if (!$this->sources->contains($source)) {
            $this->sources->add($source);
        }

        return $this;
    }

    public function removeSource(Source $source): static
    {
        $this->sources->removeElement($source);

        return $this;
    }

    public function getSourcesString(): string
    {
        return implode(', ', $this->sources->map(fn(Source $s) => $s->getName())->toArray());
    }

    public function getContactMethods(): Collection
    {
        return $this->contactMethods;
    }

    public function addContactMethod(ContactMethod $contactMethod): static
    {
        if (!$this->contactMethods->contains($contactMethod)) {
            $this->contactMethods->add($contactMethod);
        }

        return $this;
    }

    public function removeContactMethod(ContactMethod $contactMethod): static
    {
        $this->contactMethods->removeElement($contactMethod);

        return $this;
    }

    public function getContactMethodsString(): string
    {
        return implode(', ', $this->contactMethods->map(fn(ContactMethod $m) => $m->getName())->toArray()) ?: '(brak)';
    }

    public function isDoNotContact(): bool
    {
        foreach ($this->contactMethods as $method) {
            if ($method->isDoNotContact()) {
                return true;
            }
        }
        return false;
    }

    public function isLocalClient(): bool
    {
        return $this->isLocalClient;
    }

    public function setIsLocalClient(bool $isLocalClient): static
    {
        $this->isLocalClient = $isLocalClient;

        return $this;
    }

    public function getTurnover(): ?string
    {
        return $this->turnover;
    }

    public function setTurnover(?string $turnover): static
    {
        $this->turnover = $turnover;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

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

    public function getDeletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?\DateTimeImmutable $deletedAt): static
    {
        $this->deletedAt = $deletedAt;
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

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): static
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks->add($task);
            $task->setClient($this);
        }

        return $this;
    }

    public function removeTask(Task $task): static
    {
        if ($this->tasks->removeElement($task)) {
            if ($task->getClient() === $this) {
                $task->setClient(null);
            }
        }

        return $this;
    }

    public function getSuggestions(): Collection
    {
        return $this->suggestions;
    }

    public function addSuggestion(Suggestion $suggestion): static
    {
        if (!$this->suggestions->contains($suggestion)) {
            $this->suggestions->add($suggestion);
            $suggestion->setClient($this);
        }

        return $this;
    }

    public function removeSuggestion(Suggestion $suggestion): static
    {
        if ($this->suggestions->removeElement($suggestion)) {
            if ($suggestion->getClient() === $this) {
                $suggestion->setClient(null);
            }
        }

        return $this;
    }

    public function getNewSuggestionTitle(): ?string
    {
        return $this->newSuggestionTitle;
    }

    public function setNewSuggestionTitle(?string $newSuggestionTitle): static
    {
        $this->newSuggestionTitle = $newSuggestionTitle;

        return $this;
    }

    public function getNewSuggestionText(): ?string
    {
        return $this->newSuggestionText;
    }

    public function setNewSuggestionText(?string $newSuggestionText): static
    {
        $this->newSuggestionText = $newSuggestionText;

        return $this;
    }

    public function getSuggestionDraftTags(): Collection
    {
        return $this->suggestionDraftTags;
    }

    public function addSuggestionDraftTag(Tag $tag): static
    {
        if (!$this->suggestionDraftTags->contains($tag)) {
            $this->suggestionDraftTags->add($tag);
        }

        return $this;
    }

    public function removeSuggestionDraftTag(Tag $tag): static
    {
        $this->suggestionDraftTags->removeElement($tag);

        return $this;
    }
}

