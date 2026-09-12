@if ($isSaved)
    <form class="inline-form" method="POST" action="{{ route('opportunities.unsave', $opportunity) }}">
        @csrf
        @method('DELETE')
        <button class="btn btn-secondary" type="submit">Unsave</button>
    </form>
@else
    <form class="inline-form" method="POST" action="{{ route('opportunities.save', $opportunity) }}">
        @csrf
        <button class="btn btn-secondary" type="submit">Save</button>
    </form>
@endif
