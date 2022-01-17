<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymobController extends Controller
{
    public function index(Request $request)
    {
        $amount = $request->amount;
        $token = $this->authToken();
        $order = $this->registerOrder(
            token: $token,
            amount: $amount
        );

        // TODO: should save id in your db for this order for later usage
        Order::create([
            'paymob_order_id'   =>  $order['id'],
            'paymob_amount'     =>  ($order['amount_cents'] / 100),
            'currency'          =>  $order['currency'],
        ]);

        $paymentToken = $this->paymentKey(
            token: $token,
            amount: $amount,
            // expiration: 3600,
            order_id: $order['id'],
            email: 'some@email.com',
            first_name: 'sherif',
            last_name: 'nabil',
            phone_number: '01111111111'
        )['token'];

        return redirect("https://accept.paymob.com/api/acceptance/iframes/" . env('PAYMOB_CARD_IFRAME_ID') . "?payment_token=$paymentToken");
    }

    private function paymobUrls(): ?array
    {
        return [
            'auth'            =>  'https://accept.paymob.com/api/auth/tokens',
            'order-register'  =>  'https://accept.paymob.com/api/ecommerce/orders',
            'payment_keys'    =>  'https://accept.paymob.com/api/acceptance/payment_keys',
            'wallet_pay'      =>  'https://accept.paymob.com/api/acceptance/payments/pay',
            'get_order'       =>  'https://accept.paymobsolutions.com/api/ecommerce/orders/'
        ];
    }

    private function authRequest(): ?Response
    {
        return ($this->request(
            url: $this->paymobUrls()['auth'],
            payload: [ 'api_key' =>    env('PAYMOB_API_KEY') ]
        ));
    }

    private function auth(): ?Collection
    {
        return $this->authRequest()->json();
    }

    private function authToken(): ?string
    {
        return $this->authRequest()->json()['token'];
    }

    private function getOrder(
        int $orderId,
        string $authToken
    ): ?Response {
        return $this->request(
            url: $this->paymobUrls()['get_order'] . "{$orderId}?token={$authToken}",
            payload: [ 'api_key' =>    env('PAYMOB_API_KEY')],
            method: 'get'
        );
    }

    private function request($url, $payload, $method = 'post'): ?Response
    {
        $response = Http::withHeaders(['Content-Type' => 'application/json'])->{$method}($url, $payload);

        // if ($response->status() != 201) {
        //     throw new \Exception('Api Token Error');
        //f ($response->status() != 201) {
        //     throw new \Exception('Api Token Error');
        // } }
        return $response;
    }

    private function registerOrder(
        string $token = '',
        float $amount = 0,
        array $items = []
    ): ?array {
        $payload = [
            "auth_token"        =>  $token,
            "delivery_needed"   => "false",
            "amount_cents"      => $amount * 100,
            "currency"          => "EGP",
            "items"             => $items
        ];
        return $this->request(
            url: $this->paymobUrls()['order-register'],
            payload: $payload
        )->json();
    }

    private function paymentKey(
        string $token = '',
        float $amount = 0,
        int $expiration = 3600,
        int $order_id,
        string $email = '',
        string $first_name = '',
        string $last_name = '',
        string $phone_number = '',
    ): ?array {
        $payload = [
            "auth_token" => $token,
            "amount_cents"      => $amount * 100,
            "expiration" => $expiration,
            "order_id" => $order_id,
            "billing_data" => [
                "apartment" => "NA",
                "email" => $email,
                "floor" => "NA",
                "first_name" => $first_name,
                "last_name" => $last_name,
                "street" => "NA",
                "building" => "NA",
                "phone_number" => $phone_number,
                "shipping_method" => "NA",
                "postal_code" => "NA",
                "city" => "NA",
                "country" => "NA",
                "state" => "NA"
            ],
            "currency" => "EGP",
            "integration_id" => env('PAYMOB_INTEGERATION_ID'), // should be set to live integeration ID if live
            "lock_order_when_paid" => "false"
        ];

        return $this->request(
            url: $this->paymobUrls()['payment_keys'],
            payload: $payload
        )->json();
    }

    public function mobileWalletPay(
        string $token,
        string $mobile,
    ) {
        $payload = [
            "source"    => [
                "identifier"    => $mobile,
                "subtype"   => "WALLET"
            ],
            "payment_token"   => $token
        ];
        return $this->request(
            url: $this->paymobUrls()['wallet_pay'],
            payload: $payload
        )->json();
    }

    public function callback(Request $request): string
    {
        $hmac   = $request->hmac;
        $hashed = $this->hashedHmac($request);
        if ($hmac != $hashed) {
            return 'Manipulated redirection Data';
        }
        $order = Order::where([
            'paymob_order_id'   =>  $request->order,
            'paymob_amount'     =>  ($request->amount_cents / 100),
            'currency'          =>  $request->currency,
        ])->firstOrFail();

        if ($request->success == "true" && $request->pending == "false") {
            $order->update([
                'is_paid'   =>  true
            ]);
            return 'paid order';
        }
        return 'unpaid Order';
    }

    private function hashedHmac(Request $request): ?string
    {
        $data = $request->all();
        ksort($data);
        $concatenatedString = '';

        $hmacStringKeys = [
            "amount_cents",
            "created_at",
            "currency",
            "error_occured",
            "has_parent_transaction",
            "id",
            "integration_id",
            "is_3d_secure",
            "is_auth",
            "is_capture",
            "is_refunded",
            "is_standalone_payment",
            "is_voided",
            "order",
            "owner",
            "pending",
            "source_data_pan",
            "source_data_sub_type",
            "source_data_type",
            'success',
        ];

        foreach ($data as $key =>  $value) {
            if (in_array($key, $hmacStringKeys)) {
                $concatenatedString .= $value;
            }
        }
        return hash_hmac('SHA512', $concatenatedString, env('PAYMOB_HMAC'));
    }
}
