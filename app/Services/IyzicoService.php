<?php

namespace App\Services;

use App\Models\Fatura;
use Iyzipay\Options;
use Iyzipay\Model\CheckoutFormInitialize;
use Iyzipay\Model\Locale;
use Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Iyzipay\Model\Address;
use Iyzipay\Model\BasketItem;
use Iyzipay\Model\BasketItemType;
use Iyzipay\Model\Buyer;
use Iyzipay\Model\PaymentGroup;

class IyzicoService
{
    protected $options;
    protected $test_mode;

    public function __construct()
    {
        // Yeni şema: ayarlar tablosu. Fallback: eski iyzico tablosu (varsa).
        $a = \DB::table('ayarlar')->first();
        $apiKey    = $a->iyzico_apikey ?? null;
        $secretKey = $a->iyzico_secret ?? null;
        $baseUrl   = $a->iyzico_base   ?? null;
        // Iyzico'da test mode genelde sandbox URL ile belirlenir; ayrı kolon yok, base'e bakılır.

        if (!$apiKey || !$secretKey) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('iyzico')) {
                    $iyzico = \DB::table('iyzico')->first();
                    if ($iyzico) {
                        $apiKey    = $apiKey    ?: ($iyzico->api_key    ?? '');
                        $secretKey = $secretKey ?: ($iyzico->secret_key ?? '');
                    }
                }
            } catch (\Throwable $e) {}
        }

        $this->options = new Options();
        $this->options->setApiKey((string) ($apiKey ?? ''));
        $this->options->setSecretKey((string) ($secretKey ?? ''));

        if ($baseUrl) {
            $this->options->setBaseUrl($baseUrl);
            $this->test_mode = (stripos($baseUrl, 'sandbox') !== false) ? 1 : 0;
        } else {
            // Varsayılan: canlı yoksa sandbox
            $this->test_mode = 1;
            $this->options->setBaseUrl("https://sandbox-api.iyzipay.com");
        }
    }

    /**
     * Iyzico ödeme formu oluştur
     */
    public function createPayment($fatura_id, $callback_url)
    {
        $fatura = Fatura::findOrFail($fatura_id);
        $uye = $fatura->uye;

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale(Locale::TR);
        $request->setConversationId('FAT' . $fatura->id);
        $request->setPrice($fatura->tutar);
        $request->setPaidPrice($fatura->toplam);
        $request->setCurrency(\Iyzipay\Model\Currency::TL);
        $request->setBasketId('B' . $fatura->id);
        $request->setPaymentGroup(PaymentGroup::PRODUCT);
        $request->setCallbackUrl($callback_url);
        $request->setEnabledInstallments([1, 2, 3, 6, 9, 12]);

        // Alıcı bilgileri
        $buyer = new Buyer();
        $buyer->setId('BY' . $uye->id);
        $buyer->setName($uye->ad);
        $buyer->setSurname($uye->soyad);
        $buyer->setGsmNumber($uye->telefon ?? '05555555555');
        $buyer->setEmail($uye->email);
        $buyer->setIdentityNumber($uye->tc_no ?? '11111111110');
        $buyer->setRegistrationAddress($uye->adres ?? 'Adres');
        $buyer->setIp(request()->ip());
        $buyer->setCity($uye->sehir ?? 'Istanbul');
        $buyer->setCountry($uye->ulke ?? 'Turkey');
        $buyer->setZipCode($uye->posta_kodu ?? '34000');
        $request->setBuyer($buyer);

        // Fatura adresi
        $billingAddress = new Address();
        $billingAddress->setContactName($uye->ad . ' ' . $uye->soyad);
        $billingAddress->setCity($uye->sehir ?? 'Istanbul');
        $billingAddress->setCountry($uye->ulke ?? 'Turkey');
        $billingAddress->setAddress($uye->fatura_adres ?? $uye->adres ?? 'Adres');
        $billingAddress->setZipCode($uye->posta_kodu ?? '34000');
        $request->setBillingAddress($billingAddress);

        // Teslimat adresi
        $request->setShippingAddress($billingAddress);

        // Sepet ürünleri
        $basketItems = [];
        $basketItem = new BasketItem();
        $basketItem->setId('ITEM' . $fatura->id);
        $basketItem->setName('Fatura #' . $fatura->fatura_no);
        $basketItem->setCategory1('Fatura');
        $basketItem->setItemType(BasketItemType::VIRTUAL);
        $basketItem->setPrice($fatura->toplam);
        $basketItems[] = $basketItem;
        $request->setBasketItems($basketItems);

        // İsteği gönder
        $checkoutFormInitialize = CheckoutFormInitialize::create($request, $this->options);

        if ($checkoutFormInitialize->getStatus() == 'success') {
            return [
                'status' => 'success',
                'token' => $checkoutFormInitialize->getToken(),
                'checkoutFormContent' => $checkoutFormInitialize->getCheckoutFormContent(),
                'paymentPageUrl' => $checkoutFormInitialize->getPaymentPageUrl(),
            ];
        } else {
            throw new \Exception($checkoutFormInitialize->getErrorMessage());
        }
    }

    /**
     * Iyzico callback doğrulama
     */
    public function verifyCallback($token)
    {
        $request = new \Iyzipay\Request\RetrieveCheckoutFormRequest();
        $request->setLocale(Locale::TR);
        $request->setToken($token);

        $checkoutForm = \Iyzipay\Model\CheckoutForm::retrieve($request, $this->options);

        if ($checkoutForm->getStatus() == 'success' && $checkoutForm->getPaymentStatus() == 'SUCCESS') {
            return [
                'status' => 'success',
                'payment_id' => $checkoutForm->getPaymentId(),
                'conversation_id' => $checkoutForm->getConversationId(),
                'price' => $checkoutForm->getPrice(),
                'paid_price' => $checkoutForm->getPaidPrice(),
            ];
        } else {
            return [
                'status' => 'failed',
                'error' => $checkoutForm->getErrorMessage(),
            ];
        }
    }
}







