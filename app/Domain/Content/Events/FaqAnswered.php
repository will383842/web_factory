<?php

declare(strict_types=1);

namespace App\Domain\Content\Events;

use App\Domain\Shared\Events\DomainEvent;

final class FaqAnswered extends DomainEvent
{
    public function __construct(
        public readonly string $faqId,
        public readonly string $projectId,
        public readonly string $question,
    ) {
        parent::__construct();
    }

    public function aggregateId(): string
    {
        return $this->faqId;
    }

    public function eventName(): string
    {
        return 'content.faq.answered';
    }
}
