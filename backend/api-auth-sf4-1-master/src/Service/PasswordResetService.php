<?php

namespace App\Service;

use App\Entity\PasswordResetToken;
use App\Repository\PasswordResetTokenRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordResetService
{
    private const TOKEN_TTL_MINUTES = 45;

    public function __construct(
        private readonly UsuarioRepository $usuarios,
        private readonly PasswordResetTokenRepository $tokens,
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly LoggerInterface $logger,
        private readonly string $frontendUrl,
        private readonly string $mailerFrom = 'no-reply@heos.local',
    ) {
    }

    /**
     * Siempre se comporta igual exista o no el usuario, para no revelar
     * qué nombres de usuario están registrados.
     */
    public function requestReset(string $nombreUsuario, ?string $requestIp): void
    {
        $usuario = $this->usuarios->findOneByUsuario($nombreUsuario);

        if ($usuario === null || !$usuario->isEstado()) {
            return;
        }

        $rawToken = bin2hex(random_bytes(32));

        $resetToken = new PasswordResetToken();
        $resetToken->setUsuario($usuario);
        $resetToken->setTokenHash(hash('sha256', $rawToken));
        $resetToken->setExpiresAt(new \DateTimeImmutable(sprintf('+%d minutes', self::TOKEN_TTL_MINUTES)));
        $resetToken->setRequestedIp($requestIp);

        $this->em->persist($resetToken);
        $this->em->flush();

        $resetLink = sprintf('%s/auth/reset-password/%s', rtrim($this->frontendUrl, '/'), $rawToken);

        $emailMessage = (new Email())
            ->from($this->mailerFrom)
            ->to($usuario->getEmail())
            ->subject('Recuperación de contraseña — HEOS 4.1')
            ->text(sprintf(
                "Hola %s,\n\nSolicitaste recuperar tu contraseña. Este enlace vence en %d minutos:\n%s\n\nSi no fuiste tú, ignora este correo.",
                $usuario->getNombre(),
                self::TOKEN_TTL_MINUTES,
                $resetLink,
            ));

        $this->mailer->send($emailMessage);

        // MAILER_DSN=null:// en dev descarta el envío real; se deja constancia
        // del enlace en el log para poder probar el flujo completo sin SMTP.
        $this->logger->info('Password reset link generado', [
            'usuario_id' => $usuario->getId(),
            'email' => $usuario->getEmail(),
            'reset_link' => $resetLink,
            'expires_at' => $resetToken->getExpiresAt()->format(DATE_ATOM),
        ]);
    }

    /**
     * @throws \DomainException si el token es inválido, expirado o ya usado
     */
    public function resetPassword(string $rawToken, string $newPassword): void
    {
        $tokenHash = hash('sha256', $rawToken);
        $resetToken = $this->tokens->findOneByTokenHash($tokenHash);
        
        if ($resetToken === null || $resetToken->isUsed() || $resetToken->isExpired()) {
            throw new \DomainException('El enlace de recuperación es inválido o ya expiró.');
        }

        $usuario = $resetToken->getUsuario();
        $usuario->setPassword($this->passwordHasher->hashPassword($usuario, $newPassword));
        $resetToken->setUsedAt(new \DateTimeImmutable());

        $this->em->flush();
    }
}
