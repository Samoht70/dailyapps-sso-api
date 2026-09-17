<?php

namespace Technical\Oidc\Tests\Feature;

use Tests\TestCase;

class OidcTest extends TestCase
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
