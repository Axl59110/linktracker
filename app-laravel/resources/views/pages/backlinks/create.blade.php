@extends('layouts.app')

@section('title', 'Créer un backlink - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700">Backlinks</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Nouveau backlink</span>
@endsection

@section('content')
    <x-page-header title="Créer un backlink" subtitle="Ajoutez un nouveau backlink à surveiller" />

    <div class="bg-white p-8 rounded-lg border border-neutral-200">
        <x-backlink-form
            :action="route('backlinks.store')"
            method="POST"
            :projects="$projects"
            :platforms="$platforms"
            :tier1Backlinks="$tier1Backlinks"
            :selectedProjectId="$selectedProjectId"
            submitText="Créer le backlink"
            :cancelRoute="route('backlinks.index')"
        />
    </div>
@endsection
