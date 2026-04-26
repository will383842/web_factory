<?php

declare(strict_types=1);

namespace App\Domain\Content\Entities;

use App\Domain\Content\Events\FaqAnswered;
use App\Domain\Content\ValueObjects\ContentStatus;
use App\Domain\Shared\Entities\AggregateRoot;
use App\Domain\Shared\ValueObjects\Locale;

final class Faq extends AggregateRoot
{
    private function __construct(
        public readonly string $id,
        public readonly string $projectId,
        public readonly Locale $locale,
        public readonly string $question,
        public readonly string $answer,
        public readonly ?string $category,
        public readonly bool $isFeatured,
        public ContentStatus $status,
        public int $viewCount,
        public int $helpfulCount,
    ) {}

    public static function answer(
        string $id,
        string $projectId,
        Locale $locale,
        string $question,
        string $answer,
        ?string $category = null,
        bool $isFeatured = false,
    ): self {
        $faq = new self(
            id: $id,
            projectId: $projectId,
            locale: $locale,
            question: $question,
            answer: $answer,
            category: $category,
            isFeatured: $isFeatured,
            status: ContentStatus::Draft,
            viewCount: 0,
            helpfulCount: 0,
        );
        $faq->recordEvent(new FaqAnswered($id, $projectId, $question));

        return $faq;
    }

    public static function rehydrate(
        string $id,
        string $projectId,
        Locale $locale,
        string $question,
        string $answer,
        ?string $category,
        bool $isFeatured,
        ContentStatus $status,
        int $viewCount,
        int $helpfulCount,
    ): self {
        return new self($id, $projectId, $locale, $question, $answer, $category, $isFeatured, $status, $viewCount, $helpfulCount);
    }

    public function publish(): void
    {
        $this->status = ContentStatus::Published;
    }
}
