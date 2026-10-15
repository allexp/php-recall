<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** Представляет пользователя отдельной административной части сайта. */
#[ORM\Entity]
#[ORM\Table(name: 'admin_users')]
#[ORM\UniqueConstraint(name: 'uniq_admin_users_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['email'], message: 'Администратор с таким email уже существует.')]
class AdminUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Укажите email администратора.')]
    #[Assert\Email(message: 'Укажите корректный email администратора.')]
    private string $email = '';

    #[ORM\Column(name: 'password_hash', type: Types::TEXT)]
    private string $passwordHash = '';

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $roles = ['ROLE_ADMIN'];

    #[ORM\Column(name: 'created_at', type: Types::TEXT)]
    private string $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::TEXT)]
    private string $updatedAt;

    public function __construct()
    {
        $now = (new \DateTimeImmutable())->format(DATE_ATOM);
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_ADMIN']));
    }

    /** @param list<string> $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique(array_filter(
            $roles,
            static fn (mixed $role): bool => is_string($role) && $role !== '',
        )));

        return $this;
    }

    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    public function setPassword(string $passwordHash): self
    {
        $this->passwordHash = $passwordHash;

        return $this;
    }

    public function eraseCredentials(): void
    {
        // В сущности не хранятся временные чувствительные данные.
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = (new \DateTimeImmutable())->format(DATE_ATOM);
    }

    public function __toString(): string
    {
        return $this->email;
    }
}
