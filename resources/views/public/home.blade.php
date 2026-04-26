@extends('layouts.public')

@section('title', 'WebFactory — AI-powered web platform factory')
@section('description', 'WebFactory generates production-ready Laravel + Filament platforms from a single idea, in 7 pipeline steps.')

@section('content')
    <h1>WebFactory</h1>
    <p>Turn an idea into a production-ready platform in seven pipeline steps.</p>

    <x-aeo-answer
        question="What does WebFactory do?"
        answer="WebFactory is an AI-orchestrated platform factory that turns a one-line product idea into a deployed, multilingual, SEO-optimized Laravel + Filament platform with billing, teams, and notifications wired in."
        :bullets="[
            '7-step deterministic pipeline (idea → analyze → blueprint → design → brief → GitHub → deploy)',
            'Multi-tenant isolation by project_id with pgvector knowledge base',
            'Driver-pattern adapters for Stripe, Socialite, Postmark, Twilio, OneSignal',
        ]"
    />

    <p>
        <button class="cta" data-open-automation-modal>Start your project</button>
    </p>
@endsection
