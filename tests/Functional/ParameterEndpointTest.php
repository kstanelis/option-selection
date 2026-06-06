<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\Support\SeedsParameterData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ParameterEndpointTest extends WebTestCase
{
    use SeedsParameterData;

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->clearParameterData($em);
        $this->seedParameterData($em);
    }

    public function testGetParameterNoSelection(): void
    {
        $this->client->request('GET', '/api/parameter');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['A', 'B', 'C'], $data['parameter1']);
        $this->assertSame(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter1A(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=A');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['A'], $data['parameter1']);
        $this->assertSame(['X', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter1B(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=B');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['B'], $data['parameter1']);
        $this->assertSame(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter2Z(): void
    {
        $this->client->request('GET', '/api/parameter?parameter2=Z');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['A', 'B'], $data['parameter1']);
        $this->assertSame(['Z'], $data['parameter2']);
    }

    public function testGetParameterWithValidBothSelectedEchoesBoth(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=A&parameter2=X');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame(['A'], $data['parameter1']);
        $this->assertSame(['X'], $data['parameter2']);
    }

    public function testGetParameterWithForbiddenBothSelectedReturnsEmpty(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=A&parameter2=Y');
        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame([], $data['parameter1']);
        $this->assertSame([], $data['parameter2']);
    }

    public function testGetParameterWithInvalidValue(): void
    {
        $this->client->request('GET', '/api/parameter?parameter1=Q');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetParameterWithInvalidParameter(): void
    {
        $this->client->request('GET', '/api/parameter?parameter3=foo');
        $this->assertResponseStatusCodeSame(400);
    }
}
