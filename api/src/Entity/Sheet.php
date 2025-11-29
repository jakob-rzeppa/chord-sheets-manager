<?php

namespace App\Entity;

use App\Dto\CreateSheetRequestDto;
use App\Repository\ArtistRepository;
use App\Repository\SheetRepository;
use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SheetRepository::class)]
class Sheet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $title = "";

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $capo = null;

    // The source URL points to the original source of the tab
    #[ORM\Column(type: Types::STRING)]
    private ?string $sourceURL = "";

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = "";

    /**
     * Many Sheets have Many Tags.
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class)]
    private Collection $tags;

    #[ORM\ManyToOne(inversedBy: 'tabs')]
    private ?Artist $artist = null;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCapo(): ?int
    {
        return $this->capo;
    }

    public function setCapo(int $capo): static
    {
        $this->capo = $capo;

        return $this;
    }

    public function getSourceURL(): ?string
    {
        return $this->sourceURL;
    }

    public function setSourceURL(string $sourceURL): static
    {
        $this->sourceURL = $sourceURL;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);

        return $this;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): static
    {
        $this->artist = $artist;

        return $this;
    }
}
