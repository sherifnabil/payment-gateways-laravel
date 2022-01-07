<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use PayPal\Api\Payer;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Rest\ApiContext;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use Illuminate\Http\Request;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;

class PaypalController extends Controller
{
    private function apiContext(): ApiContext
    {
        return new ApiContext(
            new OAuthTokenCredential(
                clientId: env('PAYPAL_CLIENT_ID'),
                clientSecret: env('PAYPAL_CLIENT_SECRET')
            )
        );
        // For Going Live
        // ->setConfig(
        //     ['mode' => 'live']
        // );
        // Get Live Credentials from Dashboard
    }

    public function index(Request $request)
    {
        $apiContext = $this->apiContext();

        $payer = new Payer();
        $payer->setPaymentMethod(payment_method:'paypal');

        $amount = new Amount();
        $amount->setTotal(total: $request->amount);
        $amount->setCurrency(currency: 'USD');

        $transaction = new Transaction();
        $transaction->setAmount(amount: $amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl(return_url: route('paypal.return'))
        ->setCancelUrl(cancel_url: route('paypal.cancel'));

        $payment = new Payment();
        $payment->setIntent(intent: 'sale')
        ->setPayer(payer: $payer)
        ->setTransactions(transactions: [$transaction])
        ->setRedirectUrls(redirect_urls: $redirectUrls);

        try {
            $payment->create(apiContext: $apiContext);
            return redirect(to: $payment->getApprovalLink());
        } catch (PayPalConnectionException $ex) {
            dd($ex->getData());
        }
    }

    public function paypalReturn(Request $request): Payment
    {
        $apiContext = $this->apiContext();

        try {
            $payment = Payment::get(
                paymentId: $request->paymentId,
                apiContext: $apiContext
            );
        } catch (\Exception $ex) {
            dd($ex);
        }

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId(payer_id: $request->PayerID);

        try {
            $result = $payment->execute(paymentExecution: $execution, apiContext: $apiContext);
        } catch (PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        }
        // Do your stuff with the payer and payment store it in DB or whatever
        // dd($result->state == 'approved'); // if state is approved so the payment is successed otherwise it's not
        return $result;
    }

    public function paypalCancel(): void
    {
        dd('canceled');
    }
}
