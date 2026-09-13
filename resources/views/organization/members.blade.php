@extends('organization.layout')

@section('title', 'Members')

@section('content')
    <h2>Members</h2>
    <p>Owners can add an existing PathForge user by email. The last owner cannot be removed.</p>

    @if ($isOwner)
        <form method="POST" action="{{ route('organization.members.store') }}" style="margin-bottom: 24px;">
            @csrf
            <div class="field">
                <label for="email">User email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div class="field">
                <label for="role">Role</label>
                <select id="role" name="role">
                    <option value="member" @selected(old('role', 'member') === 'member')>Member</option>
                    <option value="owner" @selected(old('role') === 'owner')>Owner</option>
                </select>
            </div>
            <button class="btn" type="submit">Add member</button>
        </form>
    @endif

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                @if ($isOwner)
                    <th></th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($members as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ ucfirst($member->pivot->role) }}</td>
                    @if ($isOwner)
                        <td>
                            <form class="inline-form" method="POST" action="{{ route('organization.members.update', $member) }}">
                                @csrf
                                @method('PUT')
                                <select name="role">
                                    <option value="member" @selected($member->pivot->role === 'member')>Member</option>
                                    <option value="owner" @selected($member->pivot->role === 'owner')>Owner</option>
                                </select>
                                <button class="btn" type="submit">Update</button>
                            </form>
                            <form class="inline-form" method="POST" action="{{ route('organization.members.destroy', $member) }}" data-pf-confirm="Remove this member?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger" type="submit">Remove</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection
