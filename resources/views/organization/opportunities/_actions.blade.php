<a href="{{ route('organization.opportunities.show', $opportunity) }}">View</a>
@if ($isOwner && ($opportunity->isDraft() || $opportunity->isPending() || $opportunity->isRejected()))
    <a href="{{ route('organization.opportunities.edit', $opportunity) }}">Edit</a>
@endif
@if ($isOwner && ($opportunity->isDraft() || $opportunity->isRejected()))
    <form class="inline-form" method="POST" action="{{ route('organization.opportunities.submit', $opportunity) }}">
        @csrf
        <button class="btn" type="submit">{{ $opportunity->isRejected() ? 'Resubmit' : 'Submit for Review' }}</button>
    </form>
@endif
@if ($isOwner && $opportunity->isDraft())
    <form class="inline-form" method="POST" action="{{ route('organization.opportunities.destroy', $opportunity) }}" onsubmit="return confirm('Delete this draft?');">
        @csrf
        @method('DELETE')
        <button class="btn btn-danger" type="submit">Delete</button>
    </form>
@endif
