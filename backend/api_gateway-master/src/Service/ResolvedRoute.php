<?php

declare(strict_types=1);

namespace App\Service;

final class ResolvedRoute
{
    public function __construct(
        public readonly string $targetBaseUrl,
        public readonly string $targetPath,
    ) {
    }

    public function url(string $queryString): string
    {
        $url = $this->targetBaseUrl . $this->targetPath;

        return $queryString !== '' ? $url . '?' . $queryString : $url;
    }
}
