@if ($paginator->hasPages())
<nav>
    <ul class="pagination">

        {{-- Önceki --}}
        @if ($paginator->onFirstPage())
            <li class="page-item disabled"><span class="page-link">&#8249;</span></li>
        @else
            <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&#8249;</a></li>
        @endif

        {{-- Sayfa numaraları --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <li class="page-item disabled"><span class="page-link">{{ $element }}</span></li>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Sonraki --}}
        @if ($paginator->hasMorePages())
            <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&#8250;</a></li>
        @else
            <li class="page-item disabled"><span class="page-link">&#8250;</span></li>
        @endif

    </ul>
    <div style="text-align:center;font-size:12px;color:rgba(255,255,255,.4);margin-top:6px">
        {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} / {{ $paginator->total() }} kayıt
    </div>
</nav>
@endif
