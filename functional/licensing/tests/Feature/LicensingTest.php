<?php

namespace Functional\Licensing\Tests\Feature;

use Tests\TestCase;

class LicensingTest extends TestCase
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
