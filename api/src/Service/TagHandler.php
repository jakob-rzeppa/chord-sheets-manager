<?php

namespace App\Service;

use App\Dto\Request\UpdateTagRequestDto;
use App\Entity\Tag;
use App\Repository\TagRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TagHandler
{
    public function __construct(
        private TagRepository $tagRepository,
        private EntityManagerInterface $entityManager,
        private SheetHandler $sheetHandler
    ) {}

    /**
     * @return Tag|null Tag if created, null if a tag with the same name already exists.
     */
    public function createTag(string $name)
    {
        $existingTag = $this->tagRepository->findOneBy(['name' => $name]);
        if ($existingTag !== null) {
            return null;
        }

        $tag = new Tag();
        $tag->setName($name);

        $this->entityManager->persist($tag);
        $this->entityManager->flush();

        return $tag;
    }

    public function updateTag(int $id, UpdateTagRequestDto $updateTagRequestDto)
    {
        $tag = $this->tagRepository->find($id);
        if ($tag === null) {
            throw new NotFoundHttpException('Tag with id ' . $id . ' not found.');
        }

        if ($updateTagRequestDto->name !== null) {
            $tag->setName($updateTagRequestDto->name);
        }

        $this->entityManager->flush();

        return $tag;
    }

    public function deleteTag(int $id): void
    {
        $tag = $this->tagRepository->find($id);
        if ($tag === null) {
            throw new NotFoundHttpException('Tag with id ' . $id . ' not found.');
        }

        $this->sheetHandler->removeTagFromAllSheets($id);

        $this->entityManager->remove($tag);
        $this->entityManager->flush();
    }
}
