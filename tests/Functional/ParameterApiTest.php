<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\ForbiddenCombination;
use App\Entity\Parameter;
use App\Entity\ParameterOption;

class ParameterApiTest extends WebTestCase
{
    private function setupTestData(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getConnection()->executeQuery('DELETE FROM forbidden_combination');
        $em->getConnection()->executeQuery('DELETE FROM parameter_option');
        $em->getConnection()->executeQuery('DELETE FROM parameter');

        $parameter1 = new Parameter('parameter1');
        $optionA = new ParameterOption($parameter1, 'A', 0);
        $optionB = new ParameterOption($parameter1, 'B', 1);
        $optionC = new ParameterOption($parameter1, 'C', 2);
        $parameter1->addOption($optionA);
        $parameter1->addOption($optionB);
        $parameter1->addOption($optionC);

        $parameter2 = new Parameter('parameter2');
        $optionX = new ParameterOption($parameter2, 'X', 0);
        $optionY = new ParameterOption($parameter2, 'Y', 1);
        $optionZ = new ParameterOption($parameter2, 'Z', 2);
        $parameter2->addOption($optionX);
        $parameter2->addOption($optionY);
        $parameter2->addOption($optionZ);

        $em->persist($parameter1);
        $em->persist($parameter2);
        $em->flush();

        $forbiddenAY = new ForbiddenCombination($optionA, $optionY);
        $forbiddenCZ = new ForbiddenCombination($optionC, $optionZ);
        $em->persist($forbiddenAY);
        $em->persist($forbiddenCZ);
        $em->flush();
    }

    public function testGetParameterWithoutSelectionReturnsAllOptions(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A', 'B', 'C'], $data['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter1BReturnsFilteredOptions(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=B');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['B'], $data['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter1AFiltersOutY(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=A');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A'], $data['parameter1']);
        $this->assertEquals(['X', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithParameter2ZFiltersOutC(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter2=Z');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A', 'B'], $data['parameter1']);
        $this->assertEquals(['Z'], $data['parameter2']);
    }

    public function testGetParameterWithInvalidParameter1ValueReturns422(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=Q');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testGetParameterWithUnknownParameterIgnoresIt(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter3=foo');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A', 'B', 'C'], $data['parameter1']);
        $this->assertEquals(['X', 'Y', 'Z'], $data['parameter2']);
    }

    public function testGetParameterWithForbiddenDualSelectionAYReturnsEmptyArrays(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=A&parameter2=Y');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([], $data['parameter1']);
        $this->assertEquals([], $data['parameter2']);
    }

    public function testGetParameterWithForbiddenDualSelectionCZReturnsEmptyArrays(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=C&parameter2=Z');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals([], $data['parameter1']);
        $this->assertEquals([], $data['parameter2']);
    }

    public function testGetParameterWithValidDualSelectionAXReturnsSelectedValues(): void
    {
        $client = static::createClient();
        $this->setupTestData();
        $client->request('GET', '/api/parameter?parameter1=A&parameter2=X');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(['A'], $data['parameter1']);
        $this->assertEquals(['X'], $data['parameter2']);
    }
}
