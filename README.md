# Laravel CRM ve Yonetim Paneli

Ajans ici kullanilan; musteri, domain/hosting takibi, teklif, gider ve insan kaynaklari moduller iceren kapsamli yonetim sistemi.

## Ozellikler

- Musteri ve domain/hosting takibi; harici saglayici hesabiyla otomatik eslestirme ve mukerrer kayit ayiklama
- Alan adi yenileme, sozlesme ve teklif hatirlatmalari (cron + e-posta)
- Gelir/gider takibi, aylik odeme hatirlatma, kar raporu
- Insan kaynaklari: personel durum panosu ve tarih arali-kli durum gecmisi raporu
- Sosyal medya paylasim plani ve isaretleme modulu
- Excel disa/ice aktarim (PhpSpreadsheet), rol bazli yetkilendirme

## Kullanilan teknolojiler

Laravel 12 - PHP 8.2 - MySQL - Blade - Bootstrap

## Bu depo hakkinda

Gercek bir musteri projesinin **portfolyo icin yayinlanmis** surumudur.
Yayina hazirlanirken canli alan adlari, gercek iletisim bilgileri, musteri
kayitlari ve uygulama anahtarlari ornek degerlerle degistirilmistir.
Kod ve mimari oldugu gibidir; veri gercek degildir.

## Kurulum

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
