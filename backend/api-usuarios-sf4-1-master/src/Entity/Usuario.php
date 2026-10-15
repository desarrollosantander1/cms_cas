<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Mapea la misma tabla gbl.usuario que administra API auth sf4-1 (dueño de su
 * migración inicial). Este servicio es dueño del CRUD; ambos deben mantener
 * columnas idénticas para no generar diffs de migración cruzados.
 */
#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'usuario', schema: 'gbl')]
#[ORM\UniqueConstraint(name: 'uq_usuario_usuario', columns: ['usuario'])]
#[ORM\UniqueConstraint(name: 'uq_usuario_email', columns: ['email'])]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'usuario', type: 'string', length: 255)]
    private string $usuario;

    #[ORM\Column(type: 'string', length: 255)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $apellido = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $telefono = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $documento = null;

    #[ORM\Column(name: 'token_web', type: 'integer', nullable: true)]
    private ?int $tokenWeb = null;

    #[ORM\Column(name: 'politica_datos', type: 'boolean')]
    private bool $politicaDatos = false;

    #[ORM\Column(name: 'politica_datos_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $politicaDatosEn = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $fallos = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $estado = true;

    #[ORM\Column(name: 'creado_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $creadoEn;

    #[ORM\Column(name: 'modificado_en', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modificadoEn = null;

    #[ORM\Column(name: 'password_modificada_en', type: 'datetime_immutable')]
    private \DateTimeImmutable $passwordModificadaEn;

    public function __construct()
    {
        $this->creadoEn = new \DateTimeImmutable();
        $this->passwordModificadaEn = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsuario(): string
    {
        return $this->usuario;
    }

    public function setUsuario(string $usuario): static
    {
        $this->usuario = $usuario;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        $this->passwordModificadaEn = new \DateTimeImmutable();

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(?string $nombre): static
    {
        $this->nombre = $nombre;

        return $this;
    }

    public function getApellido(): ?string
    {
        return $this->apellido;
    }

    public function setApellido(?string $apellido): static
    {
        $this->apellido = $apellido;

        return $this;
    }

    public function getTelefono(): ?string
    {
        return $this->telefono;
    }

    public function setTelefono(?string $telefono): static
    {
        $this->telefono = $telefono;

        return $this;
    }

    public function getDocumento(): ?string
    {
        return $this->documento;
    }

    public function setDocumento(?string $documento): static
    {
        $this->documento = $documento;

        return $this;
    }

    public function getTokenWeb(): ?int
    {
        return $this->tokenWeb;
    }

    public function setTokenWeb(?int $tokenWeb): static
    {
        $this->tokenWeb = $tokenWeb;

        return $this;
    }

    public function isPoliticaDatos(): bool
    {
        return $this->politicaDatos;
    }

    public function setPoliticaDatos(bool $politicaDatos): static
    {
        $this->politicaDatos = $politicaDatos;
        $this->politicaDatosEn = $politicaDatos ? new \DateTimeImmutable() : null;

        return $this;
    }

    public function getPoliticaDatosEn(): ?\DateTimeImmutable
    {
        return $this->politicaDatosEn;
    }

    public function getFallos(): int
    {
        return $this->fallos ?? 0;
    }

    public function isEstado(): bool
    {
        return $this->estado;
    }

    public function setEstado(bool $estado): static
    {
        $this->estado = $estado;

        return $this;
    }

    public function getCreadoEn(): \DateTimeImmutable
    {
        return $this->creadoEn;
    }

    public function getModificadoEn(): ?\DateTimeImmutable
    {
        return $this->modificadoEn;
    }

    public function tocarModificado(): static
    {
        $this->modificadoEn = new \DateTimeImmutable();

        return $this;
    }

    public function getPasswordModificadaEn(): \DateTimeImmutable
    {
        return $this->passwordModificadaEn;
    }

    // --- UserInterface / PasswordAuthenticatedUserInterface ---
    // Necesario para que el autenticador JWT pueda cargar al usuario desde el
    // claim "username" del token emitido por API auth sf4-1.

    public function getUserIdentifier(): string
    {
        return $this->usuario;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function eraseCredentials(): void
    {
    }
}
