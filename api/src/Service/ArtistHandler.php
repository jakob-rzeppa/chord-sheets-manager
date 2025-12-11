<?php

namespace App\Service;

use App\Dto\Request\UpdateArtistRequestDto;
use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ArtistHandler
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private EntityManagerInterface $entityManager,
        private SheetHandler $sheetHandler,
    ) {}

    /**
     * @return Artist|null Artist if created, null if an artist with the same name already exists.
     */
    public function createArtist(string $name): ?Artist
    {
        $existingArtist = $this->artistRepository->findOneBy(['name' => $name]);
        if ($existingArtist !== null) {
            return null;
        }

        $artist = new Artist();
        $artist->setName($name);

        $this->entityManager->persist($artist);
        $this->entityManager->flush();

        return $artist;
    }

    public function updateArtist(int $id, UpdateArtistRequestDto $updateArtistRequestDto)
    {
        $artist = $this->artistRepository->find($id);
        if ($artist === null) {
            throw new NotFoundHttpException('Artist with id ' . $id . ' not found.');
        }

        if ($updateArtistRequestDto->name !== null) {
            $artist->setName($updateArtistRequestDto->name);
        }

        $this->entityManager->flush();

        return $artist;
    }

    public function deleteArtist(int $id): void
    {
        $artist = $this->artistRepository->find($id);
        if ($artist === null) {
            throw new NotFoundHttpException('Artist with id ' . $id . ' not found.');
        }

        $this->sheetHandler->deleteArtistFromAllSheets($artist->getId());

        $this->entityManager->remove($artist);
        $this->entityManager->flush();
    }
}
