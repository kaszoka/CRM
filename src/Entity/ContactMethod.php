<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ContactMethodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ContactMethodRepository::class)]
#[ORM\Table(name: 'contact_method')]
#[UniqueEntity(fields: ['name'], message: 'Ta forma kontaktu już istnieje.')]
final class ContactMethod
{

    public const SLUG_DO_NOT_CONTACT = 'nie_kontaktowac';
    public const SLUG_NOT_INTERESTED = 'niezainteresowany';
    public const SLUG_RETAIL = 'detaliczny';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Nazwa formy kontaktu jest wymagana.')]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true, nullable: true)]
    private ?string $slug = null;

    #[ORM\ManyToMany(targetEntity: Client::class, mappedBy: 'contactMethods')]
    private Collection $clients;

    public function __construct()
    {
        $this->clients = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function isDoNotContact(): bool
    {
        return $this->slug === self::SLUG_DO_NOT_CONTACT;
    }

    public function getClients(): Collection
    {
        return $this->clients;
    }
}

