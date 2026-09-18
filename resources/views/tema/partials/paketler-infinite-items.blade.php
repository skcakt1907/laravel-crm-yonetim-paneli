@foreach($yazilimlar as $yazilim)
    <div class="col-md-4 col-sm-6 paketler-item" style="padding: 0 10px; margin-bottom: 24px;">
        @include('tema.partials.paket-karti', ['yazilim' => $yazilim])
    </div>
@endforeach

