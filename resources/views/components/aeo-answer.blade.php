{{--
    Sprint 14 — AEO direct-answer block (Spec 14 §2.1).

    Props:
        - question (string)   The question being answered (h2)
        - answer   (string)   1st sentence = full answer
        - bullets  (array)    Optional supporting bullets

    Renders a structured block at the top of any "money page" so AI assistants
    (ChatGPT, Perplexity, Google AI Overview) can extract the answer cleanly.
--}}
@props(['question', 'answer', 'bullets' => []])

<section
    class="aeo-answer rounded-lg border border-slate-200 bg-slate-50 p-6 my-6"
    aria-labelledby="aeo-question"
>
    <h2 id="aeo-question" class="text-xl font-semibold text-slate-900 mb-3">
        {{ $question }}
    </h2>

    <p class="aeo-answer__main text-base text-slate-800 leading-relaxed">
        {{ $answer }}
    </p>

    @if (! empty($bullets))
        <ul class="aeo-answer__bullets mt-4 list-disc list-inside space-y-1 text-slate-700">
            @foreach ($bullets as $bullet)
                <li>{{ $bullet }}</li>
            @endforeach
        </ul>
    @endif
</section>
