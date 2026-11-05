{{-- A notification icon: the bell is drawn here; other names go through <x-forms::icon> --}}
@if(($name ?? 'bell') === 'bell')
    <svg class="{{ $class ?? 'size-4' }}" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" data-icon-name="bell"><path d="M10.268 21a2 2 0 0 0 3.464 0"/><path d="M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326"/></svg>
@else
    <x-forms::icon :name="$name" class="{{ $class ?? 'size-4' }}" />
@endif
