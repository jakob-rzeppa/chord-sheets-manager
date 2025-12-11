<?php

namespace App\Controller;

use App\Dto\Request\CreateTagRequestDto;
use App\Dto\Request\UpdateTagRequestDto;
use App\Dto\TagDto;
use App\Entity\Tag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\TagRepository;
use App\Service\TagHandler;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

#[Route('/tags')]
final class TagController extends AbstractController
{
    #[Route('', name: 'app_tags_get_all', methods: ['GET'])]
    public function getAll(TagRepository $tagRepository): JsonResponse
    {
        $tags = $tagRepository->findAll();

        $tagPayloads = array_map(function (Tag $tag) {
            return TagDto::fromTag($tag);
        }, $tags);

        return $this->json([
            'payload' => array_map(fn(TagDto $tagPayload) => $tagPayload->toArray(), $tagPayloads),
        ]);
    }

    #[Route('/{id}', name: 'app_tags_get_by_id', methods: ['GET'])]
    public function getById(int $id, TagRepository $tagRepository): JsonResponse
    {
        $tag = $tagRepository->find($id);

        if (null === $tag) {
            throw $this->createNotFoundException();
        }

        return $this->json([
            'payload' => TagDto::fromTag($tag)->toArray()
        ]);
    }

    #[Route('', name: 'app_tags_create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload('json')] CreateTagRequestDto $createTagRequestDto,
        TagHandler $tagHandler,
    ): JsonResponse {
        $tag = $tagHandler->createTag($createTagRequestDto->name);

        # Even though the tag already exists, we return a 200 status code to indicate the request was handled successfully. It is not an problem to create a tag that already exists. This is to avoid unnecessary complication on the client side.
        if ($tag === null) {
            return $this->json([
                'message' => 'Tag with name "' . $createTagRequestDto->name . '" already exists.'
            ], 200);
        }

        return $this->json([
            'payload' => TagDto::fromTag($tag)->toArray(),
            'message' => 'Tag created successfully'
        ]);
    }

    #[Route('/{id}', name: 'app_tags_update', methods: ['PUT'])]
    public function update(
        int $id,
        #[MapRequestPayload('json')] UpdateTagRequestDto $updateTagRequestDto,
        TagHandler $tagHandler
    ): JsonResponse {
        $tag = $tagHandler->updateTag($id, $updateTagRequestDto);

        return $this->json([
            'message' => 'Tag updated successfully',
            'payload' => TagDto::fromTag($tag)->toArray(),
        ]);
    }

    #[Route('/{id}', name: 'app_tags_delete', methods: ['DELETE'])]
    public function delete(int $id, TagHandler $tagHandler): JsonResponse
    {
        $tagHandler->deleteTag($id);

        return $this->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
}
