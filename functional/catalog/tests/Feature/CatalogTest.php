<?php

namespace Functional\Catalog\Tests\Feature;

use Tests\TestCase;

class CatalogTest extends TestCase
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
