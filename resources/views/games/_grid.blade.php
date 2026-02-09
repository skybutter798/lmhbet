@foreach($games as $g)
<a
  class="gCard"
  href="{{ route('games.play', $g) }}"
  title="{{ $g->name }}"
  data-launch-game
  data-game-id="{{ $g->id }}"
>
  <div class="gThumb">
    <div class="ph ph--sq {{ $g->primaryImage ? 'has-img' : '' }}" aria-hidden="true" data-ph="{{ $g->code }}">
      @if($g->primaryImage)
        <img
          class="ph__img ph__img--cover"
          src="{{ asset(ltrim($g->primaryImage->path, '/')) }}"
          alt="{{ $g->name }}"
          loading="lazy"
        >
      @endif

      <div class="ph__label"><span>{{ $g->name }}</span></div>
    </div>
  </div>
</a>
@endforeach
