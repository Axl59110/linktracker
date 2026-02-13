@extends('layouts.app')

@section('title', 'Modifier un backlink - Link Tracker')

@section('breadcrumb')
    <a href="{{ route('backlinks.index') }}" class="text-neutral-500 hover:text-neutral-700">Backlinks</a>
    <span class="text-neutral-400 mx-2">/</span>
    <a href="{{ route('backlinks.show', $backlink) }}" class="text-neutral-500 hover:text-neutral-700">Détails</a>
    <span class="text-neutral-400 mx-2">/</span>
    <span class="text-neutral-900 font-medium">Modifier</span>
@endsection

@section('content')
    <x-page-header title="Modifier le backlink" subtitle="Mettez à jour les informations du backlink" />

    <div class="bg-white p-8 rounded-lg border border-neutral-200">
        <x-backlink-form
            :action="route('backlinks.update', $backlink)"
            method="PUT"
            :backlink="$backlink"
            :projects="$projects"
            :platforms="$platforms"
            :tier1Backlinks="$tier1Backlinks"
            submitText="Mettre à jour"
            :cancelRoute="route('backlinks.show', $backlink)"
        />
    </div>
@endsection
