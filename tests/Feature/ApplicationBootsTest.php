<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ApplicationBootsTest extends TestCase
{
    #[Test]
    public function it_answers_the_health_endpoint(): void
    {
        $this->get('/up')->assertOk();
    }
}
