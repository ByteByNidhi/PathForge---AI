@extends('admin.layout')

@section('title', 'Add organization')

@section('content')
    <h2>Add organization</h2>
    <p>Creates the organization and an owner login. The owner uses the existing PathForge login page.</p>

    <div class="actions">
        <a class="btn btn-secondary" href="{{ route('admin.organizations.index') }}">Back</a>
    </div>

    <form method="POST" action="{{ route('admin.organizations.store') }}">
        @csrf

        <div class="field">
            <label for="name">Organization name</label>
            <input id="name" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="email">Organization email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="phone">Phone</label>
            <input id="phone" name="phone" value="{{ old('phone') }}">
            @error('phone') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="website">Website</label>
            <input id="website" type="url" name="website" value="{{ old('website') }}">
            @error('website') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description">{{ old('description') }}</textarea>
            @error('description') <div class="error">{{ $message }}</div> @enderror
        </div>

        <h3>Owner account</h3>

        <div class="field">
            <label for="owner_name">Owner name</label>
            <input id="owner_name" name="owner_name" value="{{ old('owner_name') }}" required>
            @error('owner_name') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="owner_email">Owner email</label>
            <input id="owner_email" type="email" name="owner_email" value="{{ old('owner_email') }}" required>
            @error('owner_email') <div class="error">{{ $message }}</div> @enderror
        </div>

        <div class="field">
            <label for="owner_password">Owner password</label>
            <input id="owner_password" type="password" name="owner_password" required>
            @error('owner_password') <div class="error">{{ $message }}</div> @enderror
        </div>

        <button class="btn" type="submit">Create organization</button>
    </form>
@endsection
