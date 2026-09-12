@extends('organization.layout')

@section('title', 'Organization Profile')

@section('content')
    <h2>Organization profile</h2>
    <p>Public details shown with your submitted opportunities.</p>

    <form method="POST" action="{{ route('organization.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="field">
            <label for="name">Organization name</label>
            <input id="name" name="name" value="{{ old('name', $organization->name) }}" @disabled(! $isOwner) required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $organization->email) }}" @disabled(! $isOwner) required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone', $organization->phone) }}" @disabled(! $isOwner)>
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="website">Website</label>
            <input id="website" type="url" name="website" value="{{ old('website', $organization->website) }}" @disabled(! $isOwner) placeholder="https://">
            @error('website') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="logo_url">Logo URL</label>
            <input id="logo_url" type="url" name="logo_url" value="{{ old('logo_url', $organization->logo_url) }}" @disabled(! $isOwner) placeholder="https://">
            @error('logo_url') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" @disabled(! $isOwner)>{{ old('description', $organization->description) }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        @if ($isOwner)
            <button class="btn" type="submit">Save profile</button>
        @else
            <p class="muted">Only organization owners can edit this profile.</p>
        @endif
    </form>
@endsection
