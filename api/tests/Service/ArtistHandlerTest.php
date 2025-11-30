<?php

namespace App\Tests\Service;

use App\Dto\Request\UpdateArtistRequestDto;
use App\Entity\Artist;
use App\Repository\ArtistRepository;
use App\Service\ArtistHandler;
use App\Service\SheetHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArtistHandlerTest extends KernelTestCase
{
    private Container $container;
    private MockObject&ArtistRepository $artistRepositoryMock;
    private MockObject&EntityManagerInterface $entityManagerMock;
    private MockObject&SheetHandler $sheetHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = self::getContainer();

        // automatically resets and sets up mocks
        $this->artistRepositoryMock = $this->createMock(ArtistRepository::class);
        $this->container->set('App\Repository\ArtistRepository', $this->artistRepositoryMock);

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->container->set('doctrine.orm.entity_manager', $this->entityManagerMock);

        $this->sheetHandlerMock = $this->createMock(SheetHandler::class);
        $this->container->set('App\Service\SheetHandler', $this->sheetHandlerMock);
    }

    // === Create Artist Tests ===
    public function testCreateArtist(): void
    {
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $artistHandler = $this->container->get(ArtistHandler::class);
        $artist = $artistHandler->createArtist('New Artist');

        $this->assertNotNull($artist);
        $this->assertEquals('New Artist', $artist->getName());
    }

    // === Update Artist Tests ===
    public function testUpdateArtist(): void
    {
        $this->artistRepositoryMock->method('find')->willReturnCallback(function () {
            $artist = new Artist();
            $artist->setName('Old Artist');
            return $artist;
        });
        $this->entityManagerMock->expects($this->once())->method('flush');

        $artistHandler = $this->container->get(ArtistHandler::class);
        $artist = $artistHandler->updateArtist(1, new UpdateArtistRequestDto(name: 'Updated Artist'));

        $this->assertNotNull($artist);
        $this->assertEquals('Updated Artist', $artist->getName());
    }

    public function testUpdateArtistNotFound(): void
    {
        $this->artistRepositoryMock->method('find')->willReturn(null);

        $artistHandler = $this->container->get(ArtistHandler::class);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Artist with id 999 not found.');

        $artistHandler->updateArtist(999, new UpdateArtistRequestDto(name: 'Updated Artist'));
    }

    public function testUpdateArtistNoChanges(): void
    {
        $this->artistRepositoryMock->method('find')->willReturnCallback(function () {
            $artist = new Artist();
            $artist->setName('Old Artist');
            return $artist;
        });
        $this->entityManagerMock->expects($this->once())->method('flush');

        $artistHandler = $this->container->get(ArtistHandler::class);
        $artist = $artistHandler->updateArtist(1, new UpdateArtistRequestDto(name: null));

        $this->assertNotNull($artist);
        $this->assertEquals('Old Artist', $artist->getName());
    }

    // === Delete Artist Tests ===
    public function testDeleteArtist(): void
    {
        $this->artistRepositoryMock->method('find')->willReturnCallback(function () {
            $artist = new Artist();
            // The reflection class is able to change the private id property for testing purposes
            $artistReflection = new \ReflectionClass(Artist::class);
            $idProperty = $artistReflection->getProperty('id');
            $idProperty->setValue($artist, 1);
            $artist->setName('Artist to Delete');
            return $artist;
        });
        $this->sheetHandlerMock->expects($this->once())->method('deleteArtistFromAllSheets');
        $this->entityManagerMock->expects($this->once())->method('remove');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $artistHandler = $this->container->get(ArtistHandler::class);
        $artistHandler->deleteArtist(1);
    }

    public function testDeleteArtistNotFound(): void
    {
        $this->artistRepositoryMock->method('find')->willReturn(null);
        $this->sheetHandlerMock->expects($this->never())->method('deleteArtistFromAllSheets');
        $this->entityManagerMock->expects($this->never())->method('remove');
        $this->entityManagerMock->expects($this->never())->method('flush');

        $artistHandler = $this->container->get(ArtistHandler::class);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Artist with id 999 not found.');

        $artistHandler->deleteArtist(999);
    }
}
