<?php

namespace App\Dto\Request;

use Symfony\Component\Validator\Constraints as Assert;

readonly class UpdateTagRequestDto
{
    public function __construct(
        #[Assert\NotBlank(message: "Name should not be blank.", allowNull: true)]
        public ?string $name,
    ) {}
}
