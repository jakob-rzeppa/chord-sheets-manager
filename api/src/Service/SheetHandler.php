<?php

namespace App\Service;

use App\Dto\Request\CreateSheetRequestDto;
use App\Dto\Request\UpdateSheetRequestDto;
use App\Entity\Sheet;
use App\Repository\ArtistRepository;
use App\Repository\SheetRepository;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SheetHandler
{
    public function __construct(
        private SheetRepository $sheetRepository,
        private EntityManagerInterface $entityManager,
        private ArtistRepository $artistRepository,
        private TagRepository $tagRepository
    ) {}

    public function getWithLessDetailsAllSheets()
    {
        $sheets = $this->sheetRepository->findAll();

        $reducedSheets = [];

        foreach ($sheets as $sheet) {
            $artist = $sheet->getArtist() !== null ? [
                'id' => $sheet->getArtist()->getId(),
                'name' => $sheet->getArtist()->getName(),
            ] : null;

            $tags = [];
            foreach ($sheet->getTags() as $tag) {
                $tags[] = [
                    'id' => $tag->getId(),
                    'name' => $tag->getName()
                ];
            }

            $reducedSheets[] = [
                'id' => $sheet->getId(),
                'title' => $sheet->getTitle(),
                'artist' => $artist,
                'tags' => $tags
            ];
        }

        return $reducedSheets;
    }

    public function getSheetById(int $id): Sheet
    {
        $sheet = $this->sheetRepository->find($id);

        if (null === $sheet) {
            throw new NotFoundHttpException("Sheet with id $id not found.");
        }

        return $sheet;
    }

    public function createSheet(CreateSheetRequestDto $dto): Sheet
    {
        $sheet = new Sheet();
        $sheet->setTitle($dto->title);
        $sheet->setCapo($dto->capo);
        $sheet->setSourceURL($dto->source_url ?? "");
        $sheet->setContent($dto->content);

        if ($dto->artist_id !== null) {
            $artist = $this->artistRepository->find($dto->artist_id);

            $sheet->setArtist($artist);
        }

        if (!empty($dto->tag_ids)) {
            $tags = $this->tagRepository->findBy(['id' => $dto->tag_ids]);
            foreach ($tags as $tag) {
                $sheet->addTag($tag);
            }
        }

        $this->entityManager->persist($sheet);
        $this->entityManager->flush();

        return $sheet;
    }

    public function updateSheet(int $id, UpdateSheetRequestDto $dto): Sheet
    {
        $sheet = $this->sheetRepository->find($id);

        if (null === $sheet) {
            throw new NotFoundHttpException("Sheet with id $id not found.");
        }

        if (null !== $dto->title) {
            $sheet->setTitle($dto->title);
        }
        if (null !== $dto->capo) {
            $sheet->setCapo($dto->capo);
        }
        if (null !== $dto->source_url) {
            $sheet->setSourceURL($dto->source_url);
        }
        if (null !== $dto->content) {
            $sheet->setContent($dto->content);
        }

        if ($dto->artist_id !== null) {
            $artist = $this->artistRepository->find($dto->artist_id);

            $sheet->setArtist($artist);
        }

        if ($dto->tag_ids !== null) {
            $sheet->getTags()->clear();

            $tags = $this->tagRepository->findBy(['id' => $dto->tag_ids]);
            foreach ($tags as $tag) {
                $sheet->addTag($tag);
            }
        }

        $this->entityManager->flush();

        return $sheet;
    }

    public function deleteSheetById(int $id): void
    {
        $sheet = $this->sheetRepository->find($id);

        if (null === $sheet) {
            throw new NotFoundHttpException("Sheet with id $id not found.");
        }

        // remove all tag associations
        $sheet->getTags()->clear();

        $this->entityManager->remove($sheet);
        $this->entityManager->flush();
    }

    public function deleteArtistFromAllSheets(int $artistId): void
    {
        $sheets = $this->sheetRepository->findBy(['artist' => $artistId]);

        foreach ($sheets as $sheet) {
            $sheet->setArtist(null);
        }

        $this->entityManager->flush();
    }

    public function removeTagFromAllSheets(int $tagId): void
    {
        $sheets = $this->sheetRepository->findAll();
        $tagToRemove = $this->tagRepository->find($tagId);

        foreach ($sheets as $sheet) {
            if ($sheet->getTags()->contains($tagToRemove)) {
                $sheet->removeTag($tagToRemove);
            }
        }

        $this->entityManager->flush();
    }
}
