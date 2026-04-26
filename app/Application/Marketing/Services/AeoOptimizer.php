<?php

declare(strict_types=1);

namespace App\Application\Marketing\Services;

/**
 * Answer Engine Optimization scorer + suggestions (Spec 14 §AEO).
 *
 * Scores an article body on signals that make a page "answer-engine
 * friendly" for ChatGPT / Perplexity / Bing AI / Google AIO:
 *
 *  - has direct Q-style headings (5 pts each, max 25)
 *  - has short, answer-shaped paragraphs (≤ 60 words) right after H2 (15 pts)
 *  - has a TL;DR or summary block (20 pts)
 *  - has FAQ section (20 pts)
 *  - has structured lists (10 pts)
 *  - has clear definitions (10 pts)
 *
 * Total clamped to 0-100. Suggestions surface the missing axes.
 */
final class AeoOptimizer
{
    /**
     * @return array{score: int, suggestions: list<string>}
     */
    public function evaluate(string $body): array
    {
        $score = 0;
        $suggestions = [];

        // 1. Q-style headings (## Wh… ?)
        preg_match_all('/^#{2,3}\s+(Wh[a-z]+|How|Why|When|Where|Pourquoi|Comment|Quel\w*|Quand|Où)\b[^\n]*\?/imu', $body, $m);
        $qHeadings = count($m[0]);
        $score += min(25, $qHeadings * 5);
        if ($qHeadings === 0) {
            $suggestions[] = 'Add at least one question-shaped H2 heading (e.g., "## How does X work?") to surface in AI answer boxes.';
        }

        // 2. Short answer paragraph right after a heading
        if (preg_match('/^#{2,3}[^\n]+\n+([^\n]{20,400})/mu', $body, $afterHeading)) {
            $words = str_word_count($afterHeading[1]);
            if ($words <= 60) {
                $score += 15;
            } else {
                $suggestions[] = "First paragraph after a heading is {$words} words long — keep it under 60 words for direct-answer extraction.";
            }
        } else {
            $suggestions[] = 'No paragraph follows your headings — add a short summary line right under each H2.';
        }

        // 3. TL;DR
        if (preg_match('/\b(tl;dr|tldr|en bref|résumé|summary)\b/i', $body)) {
            $score += 20;
        } else {
            $suggestions[] = 'Add a TL;DR / "En bref" block at the top — Perplexity often quotes it verbatim.';
        }

        // 4. FAQ section
        if (preg_match('/^#{2,3}\s+(FAQ|Foire aux questions|Common questions)/imu', $body)) {
            $score += 20;
        } else {
            $suggestions[] = 'Add a "## FAQ" section with 3-5 Q&A pairs — direct AEO signal.';
        }

        // 5. Lists
        if (preg_match('/^[-*]\s+/mu', $body)) {
            $score += 10;
        }

        // 6. Definitions ("X is …", "X est …")
        if (preg_match('/\b(?:is|est|sont|are|signifie|means)\b\s+(?:a|an|une?|le|la|les)\b/i', $body)) {
            $score += 10;
        }

        return [
            'score' => max(0, min(100, $score)),
            'suggestions' => $suggestions,
        ];
    }
}
