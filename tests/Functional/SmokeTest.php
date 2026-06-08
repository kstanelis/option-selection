<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApiDocsPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('text/html', $client->getResponse()->headers->get('content-type'));
        $content = $client->getResponse()->getContent();
        // Swagger UI loads the docs as HTML; verify it contains the Swagger UI marker
        $this->assertStringContainsString('swagger-ui', $content);
    }

    public function testApiOpenApiSpecLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs.jsonopenapi');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        // The OpenAPI spec endpoint returns API metadata
        $this->assertArrayHasKey('title', $data['info']);
        $this->assertStringContainsString('API', $data['info']['title']);
    }

    public function testParameterEndpointReturnsValidJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/parameter');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('application/json', $client->getResponse()->headers->get('content-type'));

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('parameter1', $data);
        $this->assertArrayHasKey('parameter2', $data);
        $this->assertEquals(['A', 'B', 'C'], $data['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testParameterEndpointWithValidParameter1Filter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/parameter?parameter1=A');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A'], $data['parameter1']);
        $this->assertEquals(['X', 'Z'], $data['parameter2']);
    }

    public function testParameterEndpointWithInvalidParameterName(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/parameter?invalid_param=value');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A', 'B', 'C'], $data['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testParameterEndpointWithInvalidParameterValue(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/parameter?parameter1=Q');

        $this->assertResponseStatusCodeSame(422);
        $content = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Invalid value "Q" for parameter "parameter1"', $content['detail']);
    }
}
