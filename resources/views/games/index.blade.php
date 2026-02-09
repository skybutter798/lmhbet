@extends('layouts.app')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/games.css') }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/games.js') }}?v={{ filemtime(public_path('js/games.js')) }}" defer></script>
@endpush



@section('body')
  @include('partials.header')

  <main>
    {{-- HERO --}}
    <section class="gHero">
      <div class="wrap gHero__inner">
        <div class="gHero__copy">
          <div class="gHero__kicker">{{ $provider ? $provider->name : 'All Providers' }}</div>
          <h1 class="gHero__title">Slots</h1>
          <p class="gHero__sub">Tons of games to choose from.<br>Search, sort, and play instantly.</p>
        </div>

        <div class="gHero__art" aria-hidden="true">
          {{-- Replace with your banner image later --}}
          <div class="gHero__artBlob"></div>
        </div>
      </div>
    </section>

    {{-- PROVIDER PILLS ROW --}}
    <section class="gTopbar">
      <div class="wrap">
        {{--<div class="provRow">
          <a
            class="provPill {{ $provider ? '' : 'is-active' }}"
            href="{{ route('games.index', array_filter(['q'=>$q, 'sort'=>$sort])) }}"
            aria-current="{{ $provider ? 'false' : 'page' }}"
          >
            <span class="provPill__badge">ALL</span>
            <span class="provPill__text">All Providers</span>
          </a>
    
          @foreach($providers as $p)
            <a
              class="provPill {{ ($provider && $provider->id === $p->id) ? 'is-active' : '' }}"
              href="{{ route('games.index', array_filter(['provider'=>$p->code, 'q'=>$q, 'sort'=>$sort])) }}"
              title="{{ $p->name }}"
              aria-current="{{ ($provider && $provider->id === $p->id) ? 'page' : 'false' }}"
            >
              <span class="provPill__badge">{{ strtoupper(mb_substr($p->name, 0, 2)) }}</span>
              <span class="provPill__text">{{ $p->name }}</span>
            </a>
          @endforeach
        </div>--}}
        <a
          href="{{ route('home') }}"
          class="provPill provPill--back"
          aria-label="Go back"
        >
          <span class="provPill__badge">←</span>
          <span class="provPill__text">Back</span>
        </a>
      </div>
        

    </section>


    {{-- TOOLBAR: sort + search + hot (UI only) --}}
    {{--<section class="gTools">
      <div class="wrap">
        <form class="toolsBar" method="get" action="{{ route('games.index') }}">
          @if($providerCode)
            <input type="hidden" name="provider" value="{{ $providerCode }}">
          @endif

          <div class="tool">
            <label class="tool__label">Sorting</label>
            <select class="tool__select" name="sort">
              <option value="" {{ $sort==='' ? 'selected' : '' }}>Default</option>
              <option value="az" {{ $sort==='az' ? 'selected' : '' }}>A - Z</option>
              <option value="za" {{ $sort==='za' ? 'selected' : '' }}>Z - A</option>
            </select>
          </div>

          <div class="tool tool--search">
            <label class="tool__label">Search</label>
            <div class="tool__searchWrap">
              <input
                class="tool__input"
                type="search"
                name="q"
                value="{{ $q }}"
                placeholder="Search games..."
                autocomplete="off"
              >
              <button class="tool__btn" type="submit">Search</button>
            </div>
          </div>
          
          <div class="tool tool--hot">
            <label class="tool__label">Filter</label>
            <button
              class="tool__hotBtn"
              type="button"
              aria-pressed="false"
              title="UI only for now"
            >
              Hot Games
            </button>
          </div>
        </form>
      </div>
    </section>--}}

    {{-- GAMES GRID (lazy scroll) --}}
    <section class="gWrap">
      <div class="wrap">
        <div
          id="gamesGrid"
          class="gGrid"
          data-next="{{ $games->nextPageUrl() }}"
          data-loading="0"
        >
          @include('games._grid', ['games' => $games])
        </div>

        {{-- Sentinel for infinite scroll --}}
        <div id="gridSentinel" class="gSentinel" aria-hidden="true"></div>

        {{-- Fallback pagination (if JS disabled) --}}
        <noscript>
          <div class="gPager">
            {{ $games->links() }}
          </div>
        </noscript>

        <div id="gridStatus" class="gStatus" aria-live="polite"></div>
      </div>
    </section>
  </main>
@endsection
