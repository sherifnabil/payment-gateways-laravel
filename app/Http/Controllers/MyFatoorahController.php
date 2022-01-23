<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class MyFatoorahController extends Controller
{
    private function urls(): ?array
    {
        return [
            'invoice_payment'     =>   env('MYFATOORAH_API_URL') . '/v2/SendPayment',
            'get_payment_status'  =>   env('MYFATOORAH_API_URL') . '/v2/GetPaymentStatus',
            'initial_payment'     =>   env('MYFATOORAH_API_URL') . '/v2/InitiatePayment',
            'execute_payment'     =>   env('MYFATOORAH_API_URL') . '/v2/ExecutePayment',
            'refund_payment'      =>   env('MYFATOORAH_API_URL') . '/v2/MakeRefund',
        ];
    }

    public function index(Request $request): RedirectResponse
    {
        /**
         * approach 1
         * There are 2 approaches here you may redirect your customer
         * to a form of to your available gateways and select a one of them and process their payment
         *
         * approach 2
         * make an invoice and redirect them to the url of the invoice and there they would find
         * all possible ways to pay via myfatoorah's payment integeration
         */

        $amount = floatval($request->amount);

        if (isset($request->paymentId)) {
            $response = $this->executePayment( // approach 1
                amount: $amount,
                paymentMethodId: $request->paymentId
            );
            // here you should save the data you may need in your db for the execution part
            return redirect($response->json()['Data']['PaymentURL']);
        }

        $response = $this->makeInvoice(amount: $amount ?? 0);  // approach 2
        // here you should save the data you may need in your db for the execution part
        return redirect($response['Data']['InvoiceURL']);
    }

    public function methodForm(Request $request): View
    {
        $amount = floatval($request->amount);
        $currency = 'EGP';

        $paymentMethods = $this->initialPayment( // getting your account's available payment methods while initialization with amount and currency
            amount: $amount,
            currency: $currency
        )->json()['Data']['PaymentMethods'];

        return view('myfatoorah-method', compact('paymentMethods', 'amount', 'currency'));
    }

    private function makeInvoice(
        float $amount        = 0,
        string $customerName = "test test",
        string $currency     = "EGP",
        string $notification = "LNK",
    ): array {
        $response = $this->request(
            url: $this->urls()['invoice_payment'],
            payload: [
                "CustomerName"          => $customerName, // set customer name
                "NotificationOption"    => $notification,
                "InvoiceValue"          => $amount, //  amount to pay
                "DisplayCurrencyIso"    => $currency, // your currency
                "CallBackUrl"           => route('myfatoorah.callback'),
                "ErrorUrl"              => route('myfatoorah.error'),
            ]
        );
        return $response->json();
    }

    public function callback(Request $request): array
    {
        $response = $this->paymentStatus($request->paymentId);
        // success payment
        // do your logic here instead of returning data
        return $response->json()['Data'];
    }

    public function error(Request $request): array
    {
        $response = $this->paymentStatus($request->paymentId);
        return $response->json(); // Payment is Failed
    }

    private function request($url, $payload, $method = 'post'): ?Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . env('MYFATOORAH_API_TOKEN'),
            'Content-Type'  => 'application/json'
        ])->{$method}($url, $payload);
    }

    private function paymentStatus(string $paymentId): ?Response
    {
        return $this->request(
            url: $this->urls()['get_payment_status'],
            payload: [
                "Key"       => $paymentId,
                "KeyType"   => "PaymentId"
            ]
        );
    }

    private function initialPayment(
        float $amount    = 0,
        string $currency = "EGP",
    ): ?Response {
        return $this->request(
            url: $this->urls()['initial_payment'],
            payload: [
                "InvoiceAmount" => $amount,
                "CurrencyIso"   => $currency
            ]
        );
    }

    private function executePayment(
        float $amount   = 0,
        string $paymentMethodId
    ): ?Response {
        return $this->request(
            url: $this->urls()['execute_payment'],
            payload: [
                "InvoiceValue"      => $amount,
                "PaymentMethodId"   => $paymentMethodId,
                "CallBackUrl"       => route('myfatoorah.callback'),
                "ErrorUrl"          => route('myfatoorah.error'),
            ]
        );
    }

    public function refund(Request $request): array
    {
        $response = $this->paymentStatus($request->paymentId);
        $invoiceTransactcion = $response->json()['Data']['InvoiceTransactions'][0];

        return $this->refundRequest( // the return either success or fail as the invoice/payment is refunded
            key: $invoiceTransactcion['TransactionId'],
            amount: floatval($invoiceTransactcion['TransationValue']),
            keyType: 'PaymentId',
            comment: 'Test',
        );
    }
    private function refundRequest(
        $key,
        string $keyType = "PaymentId",
        float $amount,
        string $comment = "",
    ): ?array {
        // Note: Refund Request is not sent to the customer till it's been reviewed and executed by MyFatoorah
        $response = $this->request(
            url: $this->urls()['refund_payment'],
            payload: [
                "Key"                       => $key, // value of key type
                "KeyType"                   => $keyType,  // PaymentId or InvoiceId
                "RefundChargeOnCustomer"    => true, // for the customer bank)
                "ServiceChargeOnCustomer"   => true, // for MyFatoorah
                "Amount"                    => $amount,
                "Comment"                   => $comment,
            ]
        );
        return $response->json();
    }
}
