<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Application\Content\Services\EmbeddingService;
use App\Application\Content\Services\KnowledgeBaseSearchService;
use App\Application\Content\Services\KnowledgeChunkSearchResult;
use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Persistence + search adapter for the multi-tenant knowledge base.
 *
 * Stores chunks in the `knowledge_chunks` table (pgvector `vector(384)`
 * embedding column with HNSW cosine index). Search uses pgvector's
 * cosine-distance operator `<=>` and converts back to similarity (1-distance).
 *
 * Multi-tenant scope is mandatory: every read filters on `project_id`
 * to prevent cross-tenant leaks (a hard requirement from Spec 04).
 */
final class PgVectorKnowledgeBase implements KnowledgeBaseSearchService
{
    public function __construct(private readonly EmbeddingService $embedder) {}

    /**
     * @return array{id: int, embedding: list<float>}
     */
    public function ingest(
        string $projectId,
        string $sourceType,
        ?int $sourceId,
        string $locale,
        string $content,
        ?string $sourceUrl = null,
        ?string $topic = null,
    ): array {
        $vector = $this->embedder->embed($content);

        $row = KnowledgeChunk::query()->create([
            'project_id' => (int) $projectId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'source_url' => $sourceUrl,
            'topic' => $topic,
            'locale' => $locale,
            'content' => $content,
            'content_tokens' => str_word_count($content),
        ]);

        // pgvector stores vectors as text literal "[v1,v2,...]"
        DB::statement(
            'UPDATE knowledge_chunks SET embedding = ? WHERE id = ?',
            [$this->vectorToPg($vector), $row->getKey()],
        );

        return ['id' => (int) $row->getKey(), 'embedding' => $vector];
    }

    /**
     * @return list<KnowledgeChunkSearchResult>
     */
    public function search(string $projectId, string $query, int $limit = 5): array
    {
        $vector = $this->embedder->embed($query);
        $literal = $this->vectorToPg($vector);

        /** @var list<stdClass> $rows */
        $rows = DB::select(
            'SELECT id, project_id, source_type, source_id, content,
                    1 - (embedding <=> ?::vector) AS similarity
             FROM knowledge_chunks
             WHERE project_id = ? AND embedding IS NOT NULL
             ORDER BY embedding <=> ?::vector
             LIMIT ?',
            [$literal, (int) $projectId, $literal, $limit],
        );

        return array_values(array_map(
            static fn (stdClass $r): KnowledgeChunkSearchResult => new KnowledgeChunkSearchResult(
                chunkId: (int) $r->id,
                projectId: (string) $r->project_id,
                sourceType: (string) $r->source_type,
                sourceId: $r->source_id !== null ? (int) $r->source_id : null,
                content: (string) $r->content,
                similarity: (float) $r->similarity,
            ),
            $rows,
        ));
    }

    /**
     * @param list<float> $vector
     */
    private function vectorToPg(array $vector): string
    {
        return '['.implode(',', array_map(
            static fn (float $v): string => rtrim(rtrim(sprintf('%.8f', $v), '0'), '.'),
            $vector,
        )).']';
    }
}
