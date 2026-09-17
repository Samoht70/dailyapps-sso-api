<?php

namespace Functional\Users\Tests\Feature;

use Functional\Users\Rules\PasswordStrength;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordStrengthTest extends TestCase
{
    #[Test]
    public function it_accepts_a_password_that_meets_every_requirement(): void
    {
        $this->assertSame([], $this->reasonsFor('Correct-Horse-42!'));
    }

    /** @return array<string, array{string}> */
    public static function weakPasswords(): array
    {
        return [
            'too short' => ['Ab1!efg'],
            'no symbol' => ['Abcdefgh1234'],
            'no number' => ['Abcdefgh!!!!'],
            'no uppercase' => ['abcdefgh1234!'],
            'no letter' => ['12345678!!!!'],
        ];
    }

    #[Test]
    #[DataProvider('weakPasswords')]
    public function it_turns_away_a_password_that_falls_short(string $password): void
    {
        $this->assertNotEmpty($this->reasonsFor($password));
    }

    #[Test]
    public function it_says_what_is_missing_rather_than_that_the_password_is_weak(): void
    {
        $reasons = $this->reasonsFor('abcdefghijkl');

        $this->assertNotEmpty($reasons);
        $this->assertNotSame(['The password field is invalid.'], $reasons);
    }

    #[Test]
    public function it_asks_for_twelve_characters(): void
    {
        $this->assertSame(12, PasswordStrength::MINIMUM_LENGTH);
        $this->assertNotEmpty($this->reasonsFor('Ab1!Ab1!Ab1'));
        $this->assertSame([], $this->reasonsFor('Ab1!Ab1!Ab1!'));
    }

    /** @return list<string> */
    private function reasonsFor(string $password): array
    {
        return Validator::make(
            ['password' => $password],
            ['password' => [new PasswordStrength]],
        )->errors()->get('password');
    }
}
