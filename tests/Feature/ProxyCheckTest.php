<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProxyCheckTest extends TestCase
{
    public function test_proxy_check_returns_request_information(): void
    {
        $response = $this->get('/__proxy-check');

        $response->assertOk();

        $response->assertJsonStructure([
            'scheme',
            'is_secure',
            'host',
            'x_forwarded_proto',
            'x_forwarded_host',
            'app_url',
        ]);
    }
}
