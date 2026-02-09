@if ($paginator->hasPages())
<nav aria-label="Pagination" style="margin-top:10px;">
  <ul style="
    list-style:none;
    display:flex;
    gap:6px;
    padding:0;
    margin:0;
    flex-wrap:wrap;
    align-items:center;
  ">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
      <li><span style="padding:6px 10px; border:1px solid #1f335c; border-radius:8px; opacity:.55;">&laquo;</span></li>
    @else
      <li>
        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" style="
          padding:6px 10px;
          border:1px solid #1f335c;
          border-radius:8px;
          text-decoration:none;
          color:#7fb0ff;
          display:inline-block;
        ">&laquo;</a>
      </li>
    @endif

    {{-- Elements --}}
    @foreach ($elements as $element)
      {{-- "Three Dots" Separator --}}
      @if (is_string($element))
        <li><span style="padding:6px 10px; opacity:.7;">{{ $element }}</span></li>
      @endif

      {{-- Array Of Links --}}
      @if (is_array($element))
        @foreach ($element as $page => $url)
          @if ($page == $paginator->currentPage())
            <li>
              <span style="
                padding:6px 10px;
                border:1px solid #7fb0ff;
                border-radius:8px;
                background:rgba(127,176,255,.12);
                color:#fff;
                display:inline-block;
                font-weight:700;
              ">{{ $page }}</span>
            </li>
          @else
            <li>
              <a href="{{ $url }}" style="
                padding:6px 10px;
                border:1px solid #1f335c;
                border-radius:8px;
                text-decoration:none;
                color:#7fb0ff;
                display:inline-block;
              ">{{ $page }}</a>
            </li>
          @endif
        @endforeach
      @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
      <li>
        <a href="{{ $paginator->nextPageUrl() }}" rel="next" style="
          padding:6px 10px;
          border:1px solid #1f335c;
          border-radius:8px;
          text-decoration:none;
          color:#7fb0ff;
          display:inline-block;
        ">&raquo;</a>
      </li>
    @else
      <li><span style="padding:6px 10px; border:1px solid #1f335c; border-radius:8px; opacity:.55;">&raquo;</span></li>
    @endif
  </ul>
</nav>
@endif
