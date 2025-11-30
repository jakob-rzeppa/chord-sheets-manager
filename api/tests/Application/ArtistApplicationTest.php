<?php

namespace Api\Tests\Application;

use App\DataFixtures\ApplicationTestFixtures;
use App\Entity\Artist;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ArtistApplicationTest extends WebTestCase
{

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AbstractDatabaseTool $databaseTool;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->databaseTool = static::$kernel->getContainer()->get(DatabaseToolCollection::class)->get();

        $this->databaseTool->loadFixtures([
            ApplicationTestFixtures::class,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        unset($this->entityManager);
    }

    public function testGetAll(): void
    {
        $this->client->request('GET', '/artists');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $responseData['payload']);
        $this->assertEquals('Ed Sheeran', $responseData['payload'][0]['name']);
    }

    public function testGetById(): void
    {
        $this->client->request('GET', '/artists/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Ed Sheeran', $responseData['payload']['name']);
    }

    public function testGetByIdNotFound(): void
    {
        $this->client->request('GET', '/artists/999');

        $this->assertResponseStatusCodeSame(404);

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Artist with id 999 not found.', $responseData['message']);
    }

    public function testCreateArtist(): void
    {
        $this->client->request('POST', '/artists', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Adele',
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Adele', $responseData['payload']['name']);
        $this->assertEquals('Artist created successfully', $responseData['message']);

        $newArtist = $this->entityManager->find(Artist::class, $responseData['payload']['id']);
        $this->assertNotNull($newArtist);
        $this->assertEquals('Adele', $newArtist->getName());
    }

    public function testCreateArtistValidationError(): void
    {
        $this->client->request('POST', '/artists', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertStringContainsString('Name should not be blank.', $responseData['message']);
    }

    public function testUpdateArtist(): void
    {
        $this->client->request('PUT', '/artists/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Ed Sheeran Updated',
        ]));

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Ed Sheeran Updated', $responseData['payload']['name']);
        $this->assertEquals('Artist updated successfully', $responseData['message']);

        $updatedArtist = $this->entityManager->find(Artist::class, 1);
        $this->assertEquals('Ed Sheeran Updated', $updatedArtist->getName());
    }

    public function testUpdateArtistNotFound(): void
    {
        $this->client->request('PUT', '/artists/999', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => 'Non Existent Artist',
        ]));

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Artist with id 999 not found.', $responseData['message']);
    }

    public function testUpdateArtistValidationError(): void
    {
        $this->client->request('PUT', '/artists/1', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'name' => '',
        ]));

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertStringContainsString('Name should not be blank.', $responseData['message']);
    }

    public function testDeleteArtist(): void
    {
        $this->client->request('DELETE', '/artists/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Artist deleted successfully', $responseData['message']);

        $deletedArtist = $this->entityManager->find(Artist::class, 1);
        $this->assertNull($deletedArtist);
    }

    public function testDeleteArtistNotFound(): void
    {
        $this->client->request('DELETE', '/artists/999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals('Artist with id 999 not found.', $responseData['message']);
    }
}
