<?php

namespace App\Tests\Application;

use App\DataFixtures\ApplicationTestFixtures;
use App\Entity\Sheet;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SheetApplicationTest extends WebTestCase
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
        $this->client->request('GET', '/sheets');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('payload', $responseData);
        $this->assertCount(1, $responseData['payload']);

        $this->assertArrayHasKey('id', $responseData['payload'][0]);
        $this->assertEquals('Shape of You', $responseData['payload'][0]['title']);

        $this->assertEquals('Ed Sheeran', $responseData['payload'][0]['artist']['name']);

        $this->assertCount(2, $responseData['payload'][0]['tags']);
        $this->assertEquals('Pop', $responseData['payload'][0]['tags'][0]['name']);
        $this->assertEquals('Acoustic', $responseData['payload'][0]['tags'][1]['name']);

        $this->assertArrayNotHasKey('content', $responseData['payload'][0]);
        $this->assertArrayNotHasKey('capo', $responseData['payload'][0]);
        $this->assertArrayNotHasKey('source_url', $responseData['payload'][0]);
    }

    public function testGetById(): void
    {
        $this->client->request('GET', '/sheets/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals(1, $responseData['payload']['id']);
        $this->assertEquals('Shape of You', $responseData['payload']['title']);
        $this->assertEquals(2, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/shape-of-you', $responseData['payload']['source_url']);
        $this->assertEquals('...', $responseData['payload']['content']);

        $this->assertEquals('Ed Sheeran', $responseData['payload']['artist']['name']);

        $this->assertCount(2, $responseData['payload']['tags']);
        $this->assertEquals('Pop', $responseData['payload']['tags'][0]['name']);
        $this->assertEquals('Acoustic', $responseData['payload']['tags'][1]['name']);
    }

    public function testGetByIdNotFound(): void
    {
        $this->client->request('GET', '/sheets/999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Sheet with id 999 not found.', $responseData['message']);
    }

    public function testCreate(): void
    {
        $newSheetData = [
            'title' => 'New Song',
            'artist_id' => 1,
            'tag_ids' => [1],
            'capo' => 0,
            'source_url' => 'https://example.com/new-song',
            'content' => 'New song content...',
        ];

        $this->client->request(
            'POST',
            '/sheets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newSheetData)
        );

        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals('New Song', $responseData['payload']['title']);
        $this->assertEquals(1, $responseData['payload']['artist']['id']);
        $this->assertCount(1, $responseData['payload']['tags']);
        $this->assertEquals('Pop', $responseData['payload']['tags'][0]['name']);
        $this->assertEquals(0, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/new-song', $responseData['payload']['source_url']);
        $this->assertEquals('New song content...', $responseData['payload']['content']);

        $sheetInDatabase = $this->entityManager->getRepository(Sheet::class)->find($responseData['payload']['id']);
        $this->assertNotNull($sheetInDatabase);
        $this->assertEquals('New Song', $sheetInDatabase->getTitle());
        $this->assertEquals(1, $sheetInDatabase->getArtist()->getId());
        $this->assertCount(1, $sheetInDatabase->getTags());
        $this->assertEquals(0, $sheetInDatabase->getCapo());
        $this->assertEquals('https://example.com/new-song', $sheetInDatabase->getSourceUrl());
        $this->assertEquals('New song content...', $sheetInDatabase->getContent());
    }

    public function testCreateValidationError(): void
    {
        $invalidSheetData = [
            'title' => '',
            'artist_id' => 1,
            'tag_ids' => [1],
            'capo' => null,
            'source_url' => 'invalid-url',
            'content' => null,
        ];

        $this->client->request(
            'POST',
            '/sheets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidSheetData)
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Title should not be blank.', $responseData['message']);
        $this->assertStringContainsString('Capo should not be null.', $responseData['message']);
        $this->assertStringContainsString('Source URL should be a valid URL.', $responseData['message']);
        $this->assertStringContainsString('Content should not be null.', $responseData['message']);
    }

    // Should create the sheet with no artist and only existing tags
    public function testCreateWithNonExistingArtistAndTags(): void
    {
        $newSheetData = [
            'title' => 'New Song',
            'artist_id' => 999,
            'tag_ids' => [1, 999],
            'capo' => 0,
            'source_url' => 'https://example.com/new-song',
            'content' => 'New song content...',
        ];

        $this->client->request(
            'POST',
            '/sheets',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newSheetData)
        );

        $this->assertResponseStatusCodeSame(200);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals('New Song', $responseData['payload']['title']);
        $this->assertEquals(null, $responseData['payload']['artist']);
        $this->assertCount(1, $responseData['payload']['tags']);
        $this->assertEquals('Pop', $responseData['payload']['tags'][0]['name']);
        $this->assertEquals(0, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/new-song', $responseData['payload']['source_url']);
        $this->assertEquals('New song content...', $responseData['payload']['content']);

        $sheetInDatabase = $this->entityManager->getRepository(Sheet::class)->find($responseData['payload']['id']);
        $this->assertNotNull($sheetInDatabase);
        $this->assertEquals('New Song', $sheetInDatabase->getTitle());
        $this->assertEquals(null, $sheetInDatabase->getArtist());
        $this->assertCount(1, $sheetInDatabase->getTags());
        $this->assertEquals(0, $sheetInDatabase->getCapo());
        $this->assertEquals('https://example.com/new-song', $sheetInDatabase->getSourceUrl());
        $this->assertEquals('New song content...', $sheetInDatabase->getContent());
    }

    public function testUpdate(): void
    {
        $updatedSheetData = [
            'title' => 'Updated Song',
            'artist_id' => 1,
            'tag_ids' => [2],
            'capo' => 3,
            'source_url' => 'https://example.com/updated-song',
            'content' => 'Updated song content...',
        ];

        $this->client->request(
            'PUT',
            '/sheets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedSheetData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals('Updated Song', $responseData['payload']['title']);
        $this->assertEquals(1, $responseData['payload']['artist']['id']);
        $this->assertCount(1, $responseData['payload']['tags']);
        $this->assertEquals('Acoustic', $responseData['payload']['tags'][0]['name']);
        $this->assertEquals(3, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/updated-song', $responseData['payload']['source_url']);
        $this->assertEquals('Updated song content...', $responseData['payload']['content']);

        $sheetInDatabase = $this->entityManager->getRepository(Sheet::class)->find($responseData['payload']['id']);
        $this->assertNotNull($sheetInDatabase);
        $this->assertEquals('Updated Song', $sheetInDatabase->getTitle());
        $this->assertEquals(1, $sheetInDatabase->getArtist()->getId());
        $this->assertCount(1, $sheetInDatabase->getTags());
        $this->assertEquals(3, $sheetInDatabase->getCapo());
        $this->assertEquals('https://example.com/updated-song', $sheetInDatabase->getSourceUrl());
        $this->assertEquals('Updated song content...', $sheetInDatabase->getContent());
    }

    public function testUpdateNotFound(): void
    {
        $updatedSheetData = [
            'title' => 'Updated Song',
            'artist_id' => 1,
            'tag_ids' => [2],
            'capo' => 3,
            'source_url' => 'https://example.com/updated-song',
            'content' => 'Updated song content...',
        ];

        $this->client->request(
            'PUT',
            '/sheets/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedSheetData)
        );

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Sheet with id 999 not found.', $responseData['message']);
    }

    public function testUpdateWithNonExistingArtistAndTags(): void
    {
        $updatedSheetData = [
            'title' => 'Updated Song',
            'artist_id' => 999,
            'tag_ids' => [2, 999],
            'capo' => 3,
            'source_url' => 'https://example.com/updated-song',
            'content' => 'Updated song content...',
        ];

        $this->client->request(
            'PUT',
            '/sheets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedSheetData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals('Updated Song', $responseData['payload']['title']);
        $this->assertEquals(null, $responseData['payload']['artist']);
        $this->assertCount(1, $responseData['payload']['tags']);
        $this->assertEquals('Acoustic', $responseData['payload']['tags'][0]['name']);
        $this->assertEquals(3, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/updated-song', $responseData['payload']['source_url']);
        $this->assertEquals('Updated song content...', $responseData['payload']['content']);
    }

    public function testUpdateWithNoValues(): void
    {
        $updatedSheetData = [
            'title' => null,
            'artist_id' => null,
            'tag_ids' => null,
            'capo' => null,
            'source_url' => null,
            'content' => null,
        ];

        $this->client->request(
            'PUT',
            '/sheets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedSheetData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals('Shape of You', $responseData['payload']['title']);
        $this->assertEquals(1, $responseData['payload']['artist']['id']);
        $this->assertCount(2, $responseData['payload']['tags']);
        $this->assertEquals(2, $responseData['payload']['capo']);
        $this->assertEquals('https://example.com/shape-of-you', $responseData['payload']['source_url']);
        $this->assertEquals('...', $responseData['payload']['content']);
    }

    public function testUpdateValidationError(): void
    {
        $invalidSheetData = [
            'title' => '',
            'artist_id' => 1,
            'tag_ids' => [1],
            'capo' => null,
            'source_url' => 'invalid-url',
            'content' => null,
        ];

        $this->client->request(
            'PUT',
            '/sheets/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidSheetData)
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Title should not be blank.', $responseData['message']);
        $this->assertStringContainsString('Source URL should be a valid URL.', $responseData['message']);
    }

    public function testDelete(): void
    {
        $this->client->request('DELETE', '/sheets/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Sheet deleted successfully', $responseData['message']);

        $sheetInDatabase = $this->entityManager->getRepository(Sheet::class)->find(1);
        $this->assertEmpty($sheetInDatabase);

        // Ensure that all entries in the join table are removed
        $joinTableEntries = $this->entityManager->getConnection()->fetchAllAssociative('SELECT * FROM sheet_tag WHERE sheet_id = 1');
        $this->assertEmpty($joinTableEntries);
    }

    public function testDeleteNotFound(): void
    {
        $this->client->request('DELETE', '/sheets/999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Sheet with id 999 not found.', $responseData['message']);
    }
}
