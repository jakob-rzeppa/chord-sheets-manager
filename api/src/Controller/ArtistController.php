<?php

namespace App\Controller;

use App\Dto\ArtistDto;
use App\Dto\Request\CreateArtistRequestDto;
use App\Dto\Request\UpdateArtistRequestDto;
use App\Entity\Artist;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ArtistRepository;
use App\Service\ArtistHandler;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/artists')]
final class ArtistController extends AbstractController
{
    #[Route('', name: 'app_artists_get_all', methods: ['GET'])]
    public function getAll(ArtistRepository $artistRepository): JsonResponse
    {
        $artists = $artistRepository->findAll();

        return $this->json([
            'payload' => array_map(fn(Artist $artist) => ArtistDto::fromArtist($artist)->toArray(), $artists),
        ]);
    }

    #[Route('/{id}', name: 'app_artists_get_by_id', methods: ['GET'])]
    public function getById(int $id, ArtistRepository $artistRepository): JsonResponse
    {
        $artist = $artistRepository->find($id);

        if (null === $artist) {
            throw new NotFoundHttpException('Artist with id ' . $id . ' not found.');
        }

        return $this->json([
            'payload' => ArtistDto::fromArtist($artist)->toArray()
        ]);
    }

    #[Route('', name: 'app_artists_create', methods: ['POST'])]
    public function create(#[MapRequestPayload('json')] CreateArtistRequestDto $createArtistRequestDto, ArtistHandler $artistHandler): JsonResponse
    {
        $artist = $artistHandler->createArtist($createArtistRequestDto->name);

        # Even though the artist already exists, we return a 200 status code to indicate the request was handled successfully. It is not an problem to create an artist that already exists. This is to avoid unnecessary complication on the client side.
        if ($artist === null) {
            return $this->json([
                'message' => 'Artist with name "' . $createArtistRequestDto->name . '" already exists.'
            ], 200);
        }

        return $this->json([
            'payload' => ArtistDto::fromArtist($artist)->toArray(),
            'message' => 'Artist created successfully'
        ]);
    }

    #[Route('/{id}', name: 'app_artists_update', methods: ['PUT'])]
    public function update(int $id, #[MapRequestPayload('json')] UpdateArtistRequestDto $updateArtistRequestDto, ArtistHandler $artistHandler): JsonResponse
    {
        $artist = $artistHandler->updateArtist($id, $updateArtistRequestDto);

        return $this->json([
            'payload' => ArtistDto::fromArtist($artist)->toArray(),
            'message' => 'Artist updated successfully'
        ]);
    }

    #[Route('/{id}', name: 'app_artists_delete', methods: ['DELETE'])]
    public function delete(int $id, ArtistHandler $artistHandler): JsonResponse
    {
        $artistHandler->deleteArtist($id);

        return $this->json([
            'message' => 'Artist deleted successfully'
        ]);
    }
}
