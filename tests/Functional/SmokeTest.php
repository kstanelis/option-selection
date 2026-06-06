<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\SeedsParameterData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    use SeedsParameterData;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->clearParameterData($em);
        $this->seedParameterData($em);
    }

    public function testApiDocsPageLoads(): void
    {
        $this->client->request('GET', '/api/docs');

        $this->assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        $data = json_decode($content, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('title', $data);
        $this->assertStringContainsString('API', $data['title']);
    }

    public function testParameterEndpointReturnsValidJson(): void
    {
        $this->client->request('GET', '/api/parameter');

        $this->assertResponseIsSuccessful();
        $contentType = $this->client->getResponse()->headers->get('content-type');
        $this->assertStringContainsString('application/json', $contentType);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('parameter1', $data);
        $this->assertArrayHasKey('parameter2', $data);
        $this->assertContains('A', $data['parameter1']);
        $this->assertContains('B', $data['parameter1']);
        $this->assertContains('C', $data['parameter1']);
        $this->assertContains('X', $data['parameter2']);
        $this->assertContains('Y', $data['parameter2']);
        $this->assertContains('Z', $data['parameter2']);
    }

    public function testParameterEndpointWithValidParameter1Filter(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=A');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertContains('A', $data['parameter1']);
        $this->assertNotContains('B', $data['parameter1']);
        $this->assertNotContains('C', $data['parameter1']);
        $this->assertContains('X', $data['parameter2']);
        $this->assertNotContains('Y', $data['parameter2']);
        $this->assertContains('Z', $data['parameter2']);
    }

    public function testParameterEndpointWithInvalidParameterName(): void
    {
        $this->client->request('GET', '/api/parameter?invalid_param=value');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testParameterEndpointWithInvalidParameterValue(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=D');

        $this->assertResponseStatusCodeSame(400);
    }
}
