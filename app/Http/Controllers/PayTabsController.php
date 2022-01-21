<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\RedirectResponse;

class PayTabsController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        $response = $this->request(
            url: 'https://secure-egypt.paytabs.com/payment/request',
            payload: $this->transactionPayload(
                amount: $request->amount
            )
        );

        Transaction::create([
            'paytabs_transaction_reference' => $response->json()['tran_ref'] ?? null
        ]);
        return redirect($response['redirect_url']);
    }

    public function return(Request $request): string
    {
        $validSignature = $this->validateSignature($request->all());

        if ($validSignature) {
            if ($request->respStatus == 'A') {
                $transaction = Transaction::where('paytabs_transaction_reference', $request->tranRef)->first();
                $transaction->paid = true;
                $transaction->save();
                return 'Paid Message ' . $request->respMessage;
            }
            return 'Unpaid Message ' . $request->respMessage;
        } else {
            return 'Invalid  Transaction Signature';
        }
    }

    private function request($url, $payload, $method = 'post'): ?Response
    {
        return Http::withHeaders([
            'Authorization' => env('PAYTABS_SERVER_KEY'),
            'Content-Type' => 'application/json'
        ])->{$method}($url, $payload);
    }

    private function transactionPayload(
        float $amount    = 100,
        string $currency = "EGP",
        string $cartId   = "cart_11111",
        string $type     = "sale",
        string $class    = "ecom",
    ): ?array {
        return [
            "profile_id"        => env('PAYTABS_PROFILE_ID'),
            "tran_type"         => $type,
            "tran_class"        => $class,
            "cart_id"           => $cartId,
            "cart_description"  => "Description of the items/services",
            "cart_currency"     => $currency,
            "cart_amount"       => $amount,
            // "paypage_lang"   => "ar",
            "hide_shipping"     => true,
            "hide_billing"      => true,
            "return"            => env('PAYTABS_RETURN_URL'), // Must be HTTPS, otherwise no post data from paytabs , must be relative to your site URL
            "customer_details"  => [
                "name"      => "first last",
                "email"     => "email@domain.com",
                "phone"     => "0522222222",
                "street1"   => "address street",
                "city"      => "Cairo",
                "state"     => "zaytoon",
                "country"   => "Egypt",
                "zip"       => "12345",
                // "ip"     => "1.1.1.1"
            ]
        ];
    }

    private function validateSignature(array $data): bool
    {
        $signature = $data["signature"];
        unset($data["signature"]);

        $signature_fields = array_filter($data);
        ksort($signature_fields);

        $query = http_build_query($signature_fields);
        $signature = hash_hmac('sha256', $query, env('PAYTABS_SERVER_KEY'));
        return hash_equals($signature, $signature) === true;
    }
}
