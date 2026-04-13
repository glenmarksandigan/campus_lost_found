@if(auth()->check())
    @if(in_array(auth()->user()->type_id, [2, 4, 5, 6]))
        @include('layouts.partials.sidebar')
    @else
        @include('layouts.partials.topnav')
    @endif
@else
    @include('layouts.partials.topnav')
@endif
