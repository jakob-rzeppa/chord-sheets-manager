<?php

namespace App\Tests\Application;

use App\DataFixtures\ApplicationTestFixtures;
use App\Entity\Sheet;
use App\Entity\Tag;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TagApplicationTest extends WebTestCase
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
        $this->client->request('GET', 'api/v1/tags');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('payload', $responseData);
        $this->assertCount(2, $responseData['payload']);

        $this->assertArrayHasKey('id', $responseData['payload'][0]);
        $this->assertEquals('Pop', $responseData['payload'][0]['name']);
        $this->assertEquals('Acoustic', $responseData['payload'][1]['name']);
    }

    public function testGetById(): void
    {
        $this->client->request('GET', 'api/v1/tags/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('payload', $responseData);
        $this->assertEquals(1, $responseData['payload']['id']);
        $this->assertEquals('Pop', $responseData['payload']['name']);
    }

    public function testGetByIdNotFound(): void
    {
        $this->client->request('GET', 'api/v1/tags/999');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreate(): void
    {
        $newTagData = [
            'name' => 'Rock',
        ];

        $this->client->request(
            'POST',
            'api/v1/tags',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($newTagData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag created successfully', $responseData['message']);
        $this->assertEquals('Rock', $responseData['payload']['name']);

        $tagInDatabase = $this->entityManager->getRepository(Tag::class)->find($responseData['payload']['id']);
        $this->assertNotNull($tagInDatabase);
        $this->assertEquals('Rock', $tagInDatabase->getName());
    }

    public function testCreateDuplicateName(): void
    {
        $this->client->request(
            'POST',
            'api/v1/tags',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Pop'])
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag with name "Pop" already exists.', $responseData['message']);
    }

    public function testCreateValidationError(): void
    {
        $invalidTagData = [
            'name' => '',
        ];

        $this->client->request(
            'POST',
            'api/v1/tags',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidTagData)
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Name should not be blank.', $responseData['message']);
    }

    public function testUpdate(): void
    {
        $updatedTagData = [
            'name' => 'Updated Pop',
        ];

        $this->client->request(
            'PUT',
            'api/v1/tags/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedTagData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag updated successfully', $responseData['message']);
        $this->assertEquals('Updated Pop', $responseData['payload']['name']);

        $tagInDatabase = $this->entityManager->getRepository(Tag::class)->find($responseData['payload']['id']);
        $this->assertNotNull($tagInDatabase);
        $this->assertEquals('Updated Pop', $tagInDatabase->getName());
    }

    public function testUpdateNotFound(): void
    {
        $updatedTagData = [
            'name' => 'Updated Tag',
        ];

        $this->client->request(
            'PUT',
            'api/v1/tags/999',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedTagData)
        );

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag with id 999 not found.', $responseData['message']);
    }

    public function testUpdateWithNoChanges(): void
    {
        $updatedTagData = [
            'name' => null,
        ];

        $this->client->request(
            'PUT',
            'api/v1/tags/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($updatedTagData)
        );

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData['payload']);
        $this->assertArrayHasKey('payload', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag updated successfully', $responseData['message']);
        $this->assertEquals('Pop', $responseData['payload']['name']);

        $tagInDatabase = $this->entityManager->getRepository(Tag::class)->find($responseData['payload']['id']);
        $this->assertNotNull($tagInDatabase);
        $this->assertEquals('Pop', $tagInDatabase->getName());
    }

    public function testUpdateValidationError(): void
    {
        $invalidTagData = [
            'name' => '',
        ];

        $this->client->request(
            'PUT',
            'api/v1/tags/1',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidTagData)
        );

        $this->assertResponseStatusCodeSame(422);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertStringContainsString('Name should not be blank.', $responseData['message']);
    }

    public function testDelete(): void
    {
        $this->client->request('DELETE', 'api/v1/tags/1');

        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag deleted successfully', $responseData['message']);

        $tagInDatabase = $this->entityManager->getRepository(Tag::class)->find(1);
        $this->assertEmpty($tagInDatabase);

        // Ensure that the tag is removed from all sheets
        $sheetsWithTag = $this->entityManager->getRepository(Sheet::class)->createQueryBuilder('s')
            ->join('s.tags', 't')
            ->where('t.id = :tagId')
            ->setParameter('tagId', 1)
            ->getQuery()
            ->getResult();

        $this->assertEmpty($sheetsWithTag);

        // Ensure that the join table has no entries for the deleted tag
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->prepare('SELECT * FROM sheet_tag WHERE tag_id = :tagId');
        $stmt->bindValue('tagId', 1);
        $result = $stmt->executeQuery()->fetchAllAssociative();
        $this->assertEmpty($result);

        // Ensure that other tags are unaffected
        $otherTag = $this->entityManager->getRepository(Tag::class)->find(2);
        $this->assertNotEmpty($otherTag);
    }

    public function testDeleteNotFound(): void
    {
        $this->client->request('DELETE', 'api/v1/tags/999');

        $this->assertResponseStatusCodeSame(404);
        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('Tag with id 999 not found.', $responseData['message']);
    }
}
