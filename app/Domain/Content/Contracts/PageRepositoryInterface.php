<?php

declare(strict_types=1);

namespace App\Domain\Content\Contracts;

use App\Domain\Content\Entities\Page;
use App\Domain\Shared\ValueObjects\Locale;
use App\Domain\Shared\ValueObjects\Slug;

interface PageRepositoryInterface
{
    public function findById(string $id): ?Page;

    public function findBySlugAndLocale(Slug $slug, Locale $locale): ?Page;

    public function save(Page $page): void;

    public function delete(string $id): void;
}
