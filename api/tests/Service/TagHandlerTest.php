<?php

namespace App\Tests\Service;

use App\Dto\Request\UpdateTagRequestDto;
use App\Entity\Tag;
use App\Repository\SheetRepository;
use App\Repository\TagRepository;
use App\Service\SheetHandler;
use App\Service\TagHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagHandlerTest extends KernelTestCase
{
    private Container $container;
    private MockObject&TagRepository $tagRepositoryMock;
    private MockObject&SheetHandler $sheetHandlerMock;
    private MockObject&EntityManagerInterface $entityManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = self::getContainer();

        // automatically resets and sets up mocks
        $this->tagRepositoryMock = $this->createMock(TagRepository::class);
        $this->container->set('App\Repository\TagRepository', $this->tagRepositoryMock);

        $this->sheetHandlerMock = $this->createMock(SheetHandler::class);
        $this->container->set('App\Service\SheetHandler', $this->sheetHandlerMock);

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->container->set('doctrine.orm.entity_manager', $this->entityManagerMock);
    }

    // === Create Tag Tests ===
    public function testCreateTag(): void
    {
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $tagHandler = $this->container->get(TagHandler::class);
        $tag = $tagHandler->createTag('New Tag');

        $this->assertNotNull($tag);
        $this->assertEquals('New Tag', $tag->getName());
    }

    // === Update Tag Tests ===
    public function testUpdateTag(): void
    {
        $this->tagRepositoryMock->method('find')->willReturnCallback(function () {
            $tag = new Tag();
            $tag->setName('Old Tag');
            return $tag;
        });
        $this->entityManagerMock->expects($this->once())->method('flush');

        $tagHandler = $this->container->get(TagHandler::class);
        $tag = $tagHandler->updateTag(1, new UpdateTagRequestDto(name: 'Updated Tag'));

        $this->assertNotNull($tag);
        $this->assertEquals('Updated Tag', $tag->getName());
    }

    public function testUpdateTagNotFound(): void
    {
        $this->tagRepositoryMock->method('find')->willReturn(null);

        $tagHandler = $this->container->get(TagHandler::class);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Tag with id 999 not found.');

        $tagHandler->updateTag(999, new UpdateTagRequestDto(name: 'Updated Tag'));
    }

    public function testUpdateTagNoChanges(): void
    {
        $this->tagRepositoryMock->method('find')->willReturnCallback(function () {
            $tag = new Tag();
            $tag->setName('Old Tag');
            return $tag;
        });
        $this->entityManagerMock->expects($this->once())->method('flush');

        $tagHandler = $this->container->get(TagHandler::class);
        $tag = $tagHandler->updateTag(1, new UpdateTagRequestDto(name: null));

        $this->assertNotNull($tag);
        $this->assertEquals('Old Tag', $tag->getName());
    }

    // === Delete Tag Tests ===
    public function testDeleteTag(): void
    {
        $this->tagRepositoryMock->method('find')->willReturnCallback(function () {
            $tag = new Tag();
            $tag->setName('Tag to Delete');
            return $tag;
        });

        $this->sheetHandlerMock->expects($this->once())
            ->method('removeTagFromAllSheets')
            ->with(1);
        $this->entityManagerMock->expects($this->once())->method('remove');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $tagHandler = $this->container->get(TagHandler::class);
        $tagHandler->deleteTag(1);
    }

    public function testDeleteTagNotFound(): void
    {
        $this->tagRepositoryMock->method('find')->willReturn(null);
        $this->sheetHandlerMock->expects($this->never())->method('removeTagFromAllSheets');
        $this->entityManagerMock->expects($this->never())->method('remove');
        $this->entityManagerMock->expects($this->never())->method('flush');

        $tagHandler = $this->container->get(TagHandler::class);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Tag with id 999 not found.');

        $tagHandler->deleteTag(999);
    }
}
