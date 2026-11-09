<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tag')]
#[UniqueEntity(fields: ['name'], message: 'Ten tag już istnieje.')]
final class Tag
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Nazwa tagu jest wymagana.')]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\ManyToMany(targetEntity: Suggestion::class, mappedBy: 'tags')]
    private Collection $suggestions;

    #[ORM\ManyToMany(targetEntity: Client::class, mappedBy: 'suggestionDraftTags')]
    private Collection $clientDrafts;

    public function __construct()
    {
        $this->suggestions = new ArrayCollection();
        $this->clientDrafts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSuggestions(): Collection
    {
        return $this->suggestions;
    }

    public function getClientDrafts(): Collection
    {
        return $this->clientDrafts;
    }

    public function addClientDraft(Client $client): static
    {
        if (!$this->clientDrafts->contains($client)) {
            $this->clientDrafts->add($client);
            $client->addSuggestionDraftTag($this);
        }

        return $this;
    }

    public function removeClientDraft(Client $client): static
    {
        if ($this->clientDrafts->removeElement($client)) {
            $client->removeSuggestionDraftTag($this);
        }

        return $this;
    }
}

