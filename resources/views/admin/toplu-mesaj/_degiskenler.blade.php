{{-- Placeholder değişkenleri. Değişkenler: $phs (düz isimler, parantezsiz), $hedef (textarea id).
     NOT: Süslü parantezler Blade tarafından derlenmesin diye çalışma anında ($ac/$kapa) üretilir. --}}
@php $ac = str_repeat(chr(123), 2); $kapa = str_repeat(chr(125), 2); @endphp
<div class="tm-placeholders">
    <strong>Otomatik dolan değişkenler</strong> — tıklayınca mesaja eklenir, gönderimde her alıcıya göre dolar:<br>
    @foreach($phs as $ph)
        @php $deg = $ac . $ph . $kapa; @endphp
        <code onclick="tmDegiskenEkle('{{ $hedef }}', '{{ $deg }}')" title="Mesaja ekle" style="cursor:pointer">{{ $deg }}</code>
    @endforeach
</div>
