@php
    use Capell\Smart404\Data\Smart404SuggestionData;

    /** @var \Illuminate\Support\Collection<int, Smart404SuggestionData> $suggestions */
    $suggestions = $suggestions instanceof \Illuminate\Support\Collection ? $suggestions : collect();
    $hasSuggestions = $suggestions->isNotEmpty();
@endphp
<section
    id="capell-smart-404"
    class="capell-smart-404"
    aria-labelledby="capell-smart-404-heading"
    data-endpoint="{{ route('capell-smart-404.suggestions', [], false) }}"
    data-path="{{ request()->getPathInfo() }}"
    @if (! $hasSuggestions) hidden @endif
>
    <h2 id="capell-smart-404-heading">{{ __('capell-smart-404::generic.heading') }}</h2>
    <ul class="capell-smart-404__list">
        @foreach ($suggestions as $suggestion)
            <li><a href="{{ $suggestion->url }}">{{ $suggestion->title }}</a></li>
        @endforeach
    </ul>
</section>
<link rel="stylesheet" href="{{ route('capell-smart-404.styles', [], false) }}">
<script src="{{ route('capell-smart-404.script', [], false) }}" defer></script>
