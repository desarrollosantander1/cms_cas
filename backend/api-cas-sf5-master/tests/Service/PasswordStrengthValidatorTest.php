<?php

namespace App\Tests\Service;

use App\Service\PasswordStrengthValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PasswordStrengthValidatorTest extends TestCase
{
    private PasswordStrengthValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PasswordStrengthValidator();
    }

    /** @dataProvider passwordsInvalidas */
    public function testRechazaPasswordsQueNoCumplenLaRegla(string $password): void
    {
        $this->expectException(BadRequestHttpException::class);
        $this->validator->validar($password);
    }

    public static function passwordsInvalidas(): iterable
    {
        yield 'muy corta' => ['Abc1!'];
        yield 'sin mayuscula' => ['abcd1234!'];
        yield 'sin minuscula' => ['ABCD1234!'];
        yield 'sin numero' => ['Abcdefgh!'];
        yield 'sin caracter especial' => ['Abcd1234'];
    }

    public function testAceptaUnaPasswordQueCumpleTodasLasReglas(): void
    {
        $this->validator->validar('Abcd1234!');
        $this->addToAssertionCount(1);
    }
}
