<?php

namespace App\Tests\Service;

use App\Dto\Request\CreateSheetRequestDto;
use App\Dto\Request\UpdateSheetRequestDto;
use App\Entity\Artist;
use App\Entity\Sheet;
use App\Entity\Tag;
use App\Repository\ArtistRepository;
use App\Repository\SheetRepository;
use App\Repository\TagRepository;
use App\Service\SheetHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Container;

class SheetHandlerTest extends KernelTestCase
{
    private Container $container;
    private MockObject&SheetRepository $sheetRepositoryMock;
    private MockObject&ArtistRepository $artistRepositoryMock;
    private MockObject&TagRepository $tagRepositoryMock;
    private MockObject&EntityManagerInterface $entityManagerMock;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();
        $this->container = self::getContainer();

        // automatically resets and sets up mocks
        $this->sheetRepositoryMock = $this->createMock(SheetRepository::class);
        $this->container->set('App\Repository\SheetRepository', $this->sheetRepositoryMock);

        $this->artistRepositoryMock = $this->createMock(ArtistRepository::class);
        $this->container->set('App\Repository\ArtistRepository', $this->artistRepositoryMock);

        $this->tagRepositoryMock = $this->createMock(TagRepository::class);
        $this->container->set('App\Repository\TagRepository', $this->tagRepositoryMock);

        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->container->set('doctrine.orm.entity_manager', $this->entityManagerMock);
    }

    // === Get Sheet with less details Tests ===
    public function testGetWithLessDetailsAllSheets(): void
    {
        $this->sheetRepositoryMock->method('findAll')->willReturnCallback(function () {
            $artistReflection = new \ReflectionClass(Artist::class);
            $artistIdProperty = $artistReflection->getProperty('id');
            $artist1 = new Artist();
            $artistIdProperty->setValue($artist1, 10);
            $artist1->setName('Artist 1');

            $tagReflection = new \ReflectionClass(Tag::class);
            $tagIdProperty = $tagReflection->getProperty('id');
            $tag1 = new Tag();
            $tagIdProperty->setValue($tag1, 100);
            $tag1->setName('Tag 1');
            $tag2 = new Tag();
            $tagIdProperty->setValue($tag2, 101);
            $tag2->setName('Tag 2');

            $sheetReflection = new \ReflectionClass(Sheet::class);
            $sheetIdProperty = $sheetReflection->getProperty('id');
            $sheet1 = $sheetReflection->newInstance();
            $sheetIdProperty->setValue($sheet1, 1);
            $sheet1->setTitle('Sheet 1');
            $sheet1->setCapo(0);
            $sheet1->setContent('Content 1');
            $sheet1->setSourceURL('http://example.com/sheet1');
            // No artist and no tags for sheet1

            $sheet2 = $sheetReflection->newInstance();
            $sheetIdProperty->setValue($sheet2, 2);
            $sheet2->setTitle('Sheet 2');
            $sheet2->setCapo(2);
            $sheet2->setContent('Content 2');
            $sheet2->setSourceURL('http://example.com/sheet2');
            $sheet2->setArtist($artist1);
            $sheet2->addTag($tag1);
            $sheet2->addTag($tag2);

            return [
                $sheet1,
                $sheet2
            ];
        });

        $sheetHandler = $this->container->get(SheetHandler::class);
        $result = $sheetHandler->getWithLessDetailsAllSheets();

        $expected = [
            [
                'id' => 1,
                'title' => 'Sheet 1',
                'artist' => null,
                'tags' => []
            ],
            [
                'id' => 2,
                'title' => 'Sheet 2',
                'artist' => [
                    'id' => 10,
                    'name' => 'Artist 1'
                ],
                'tags' => [
                    [
                        'id' => 100,
                        'name' => 'Tag 1'
                    ],
                    [
                        'id' => 101,
                        'name' => 'Tag 2'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $result);
    }

    public function testGetWithLessDetailsAllSheetsEmpty(): void
    {
        $this->sheetRepositoryMock->method('findAll')->willReturn([]);

        $sheetHandler = $this->container->get(SheetHandler::class);
        $result = $sheetHandler->getWithLessDetailsAllSheets();

        $this->assertEquals([], $result);
    }

    // === Get Sheet by ID Tests ===
    public function testGetSheetById(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturnCallback(function () {
            $sheetReflection = new \ReflectionClass(Sheet::class);
            $sheetIdProperty = $sheetReflection->getProperty('id');
            $sheet = $sheetReflection->newInstance();
            $sheetIdProperty->setValue($sheet, 1);
            $sheet->setTitle('Sheet 1');
            $sheet->setCapo(0);
            $sheet->setContent('Content 1');
            $sheet->setSourceURL('http://example.com/sheet1');
            return $sheet;
        });

        $sheetHandler = $this->container->get(SheetHandler::class);
        $sheet = $sheetHandler->getSheetById(1);

        $this->assertNotNull($sheet);
        $this->assertEquals(1, $sheet->getId());
        $this->assertEquals('Sheet 1', $sheet->getTitle());
        $this->assertEquals(0, $sheet->getCapo());
        $this->assertEquals('Content 1', $sheet->getContent());
        $this->assertEquals('http://example.com/sheet1', $sheet->getSourceURL());
    }

    public function testGetSheetByIdNotFound(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturn(null);

        $sheetHandler = $this->container->get(SheetHandler::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Sheet with id 999 not found.');

        $sheetHandler->getSheetById(999);
    }

    // === Create Sheet Tests ===
    public function testCreateSheet(): void
    {
        $this->artistRepositoryMock->expects($this->once())->method('find')->willReturnCallback(function () {
            $artistReflection = new \ReflectionClass(Artist::class);
            $artistIdProperty = $artistReflection->getProperty('id');
            $artist = new Artist();
            $artistIdProperty->setValue($artist, 1);
            $artist->setName('Artist 1');
            return $artist;
        });
        $this->tagRepositoryMock->expects($this->once())->method('findBy')->willReturnCallback(function () {
            $tagReflection = new \ReflectionClass(Tag::class);
            $tagIdProperty = $tagReflection->getProperty('id');
            $tag1 = new Tag();
            $tagIdProperty->setValue($tag1, 1);
            $tag1->setName('Tag 1');
            $tag2 = new Tag();
            $tagIdProperty->setValue($tag2, 2);
            $tag2->setName('Tag 2');
            return [$tag1, $tag2];
        });
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $sheetHandler = $this->container->get(SheetHandler::class);
        $dto = new CreateSheetRequestDto(
            title: 'New Sheet',
            capo: 3,
            source_url: 'http://example.com/new-sheet',
            content: 'New sheet content',
            artist_id: 1,
            tag_ids: [1, 2]
        );
        $sheet = $sheetHandler->createSheet($dto);

        $this->assertNotNull($sheet);
        $this->assertEquals('New Sheet', $sheet->getTitle());
        $this->assertEquals(3, $sheet->getCapo());
        $this->assertEquals('http://example.com/new-sheet', $sheet->getSourceURL());
        $this->assertEquals('New sheet content', $sheet->getContent());
    }

    // === Update Sheet Tests ===
    public function testUpdateSheet(): void
    {
        $this->artistRepositoryMock->expects($this->once())->method('find')->willReturnCallback(function () {
            $artistReflection = new \ReflectionClass(Artist::class);
            $artistIdProperty = $artistReflection->getProperty('id');
            $artist = new Artist();
            $artistIdProperty->setValue($artist, 1);
            $artist->setName('Artist 1');
            return $artist;
        });
        $this->tagRepositoryMock->expects($this->once())->method('findBy')->willReturnCallback(function () {
            $tagReflection = new \ReflectionClass(Tag::class);
            $tagIdProperty = $tagReflection->getProperty('id');
            $tag1 = new Tag();
            $tagIdProperty->setValue($tag1, 1);
            $tag1->setName('Tag 1');
            $tag2 = new Tag();
            $tagIdProperty->setValue($tag2, 2);
            $tag2->setName('Tag 2');
            return [$tag1, $tag2];
        });
        $this->sheetRepositoryMock->method('find')->willReturn(new Sheet());

        $sheetHandler = $this->container->get(SheetHandler::class);

        $updatedSheet = $sheetHandler->updateSheet(999, new UpdateSheetRequestDto(
            title: 'Updated Sheet',
            capo: 2,
            source_url: 'http://example.com/updated-sheet',
            content: 'Updated content',
            artist_id: 1,
            tag_ids: [1, 2]
        ));

        $this->assertNotNull($updatedSheet);
        $this->assertEquals('Updated Sheet', $updatedSheet->getTitle());
        $this->assertEquals(2, $updatedSheet->getCapo());
        $this->assertEquals('http://example.com/updated-sheet', $updatedSheet->getSourceURL());
        $this->assertEquals('Updated content', $updatedSheet->getContent());
        $this->assertEquals(1, $updatedSheet->getArtist()?->getId());
        $this->assertCount(2, $updatedSheet->getTags());
        $this->assertEquals(1, $updatedSheet->getTags()[0]->getId());
        $this->assertEquals(2, $updatedSheet->getTags()[1]->getId());
    }

    public function testUpdateSheetNotFound(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturn(null);

        $sheetHandler = $this->container->get(SheetHandler::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Sheet with id 999 not found.');

        $sheetHandler->updateSheet(999, new UpdateSheetRequestDto(
            title: 'Updated Sheet',
            capo: 2,
            source_url: 'http://example.com/updated-sheet',
            content: 'Updated content',
            artist_id: 1,
            tag_ids: [1, 2]
        ));
    }

    public function testUpdateSheetNoChanges(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturnCallback(function () {
            $sheetReflection = new \ReflectionClass(Sheet::class);
            $sheetIdProperty = $sheetReflection->getProperty('id');
            $sheet = $sheetReflection->newInstance();
            $sheetIdProperty->setValue($sheet, 1);
            $sheet->setTitle('Original Sheet');
            $sheet->setCapo(0);
            $sheet->setSourceURL('http://example.com/original-sheet');
            $sheet->setContent('Original content');
            return $sheet;
        });

        $sheetHandler = $this->container->get(SheetHandler::class);

        $updatedSheet = $sheetHandler->updateSheet(1, new UpdateSheetRequestDto(
            title: null,
            capo: null,
            source_url: null,
            content: null,
            artist_id: null,
            tag_ids: null
        ));

        $this->assertNotNull($updatedSheet);
        $this->assertEquals('Original Sheet', $updatedSheet->getTitle());
        $this->assertEquals(0, $updatedSheet->getCapo());
        $this->assertEquals('http://example.com/original-sheet', $updatedSheet->getSourceURL());
        $this->assertEquals('Original content', $updatedSheet->getContent());
    }

    // === Delete Sheet Tests ===
    public function testDeleteSheet(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturnCallback(function () {
            $sheetReflection = new \ReflectionClass(Sheet::class);
            $sheetIdProperty = $sheetReflection->getProperty('id');
            $sheet = $sheetReflection->newInstance();
            $sheetIdProperty->setValue($sheet, 1);
            $sheet->setTitle('Sheet to Delete');
            return $sheet;
        });
        $this->entityManagerMock->expects($this->once())->method('remove');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $sheetHandler = $this->container->get(SheetHandler::class);
        $sheetHandler->deleteSheetById(1);
    }

    public function testDeleteSheetNotFound(): void
    {
        $this->sheetRepositoryMock->method('find')->willReturn(null);

        $sheetHandler = $this->container->get(SheetHandler::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->expectExceptionMessage('Sheet with id 999 not found.');

        $sheetHandler->deleteSheetById(999);
    }

    // === Helper Methods Tests ===
    public function testRemoveArtistFromAllSheets(): void
    {
        $this->sheetRepositoryMock->method('findBy')->willReturnCallback(function () {
            $mock1Sheet = $this->createMock(Sheet::class);
            $mock1Sheet->expects($this->once())->method('setArtist')->with(null);
            $mock2Sheet = $this->createMock(Sheet::class);
            $mock2Sheet->expects($this->once())->method('setArtist')->with(null);
            return [$mock1Sheet, $mock2Sheet];
        });
        $this->entityManagerMock->expects($this->exactly(1))->method('flush');

        $sheetHandler = $this->container->get(SheetHandler::class);
        $sheetHandler->deleteArtistFromAllSheets(1);
    }

    public function testRemoveTagFromAllSheets(): void
    {
        $tagToRemove = $this->createMock(Tag::class);
        $tagToRemove->method('getId')->willReturn(1);

        $this->tagRepositoryMock->method('find')->willReturn($tagToRemove);

        $this->sheetRepositoryMock->method('findAll')->willReturnCallback(function () use ($tagToRemove) {
            $mock1Sheet = $this->createMock(Sheet::class);
            $mock1Sheet->method('getTags')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([$tagToRemove]));
            $mock1Sheet->expects($this->once())->method('removeTag')->with($tagToRemove);

            $mock2Sheet = $this->createMock(Sheet::class);
            $mock2Sheet->method('getTags')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([]));
            $mock2Sheet->expects($this->never())->method('removeTag');

            return [$mock1Sheet, $mock2Sheet];
        });
        $this->entityManagerMock->expects($this->exactly(1))->method('flush');

        $sheetHandler = $this->container->get(SheetHandler::class);
        $sheetHandler->removeTagFromAllSheets(1);
    }
}
