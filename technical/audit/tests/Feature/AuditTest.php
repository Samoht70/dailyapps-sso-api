<?php

namespace Technical\Audit\Tests\Feature;

use Tests\TestCase;

class AuditTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
