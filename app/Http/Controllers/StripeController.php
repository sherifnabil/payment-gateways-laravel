<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StripeController extends Controller
{
    public function index(Request $request): array
    {
        $stripe = Stripe::setApiKey(env('STRIPE_SECRET'));
        // $token = $request->stripeToken;
        // $charge = \Stripe\Charge::create([
        //     'amount' => 999,
        //     'currency' => 'usd',
        //     'description' => 'Example charge',
        //     'source' => $token,
        // ]);

        // $session = Session::create([
        //     'line_items' => [[
        //       'price_data' => [
        //         'currency' => 'usd',
        //         'product_data' => [
        //           'name' => 'T-shirt',
        //         ],
        //         'unit_amount' => 2000,
        //       ],
        //       'quantity' => 1,
        //     ]],
        //     'mode' => 'payment',
        //     'success_url' => route('stripe.success'),
        //     'cancel_url' => route('stripe.cancel'),
        //   ]);

        try {
            // Create a PaymentIntent with amount and currency
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100, // 100 => 1,
                'currency' => 'USD',
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'clientSecret' => $paymentIntent->client_secret,
            ];
            return $output;
        } catch (\Error $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function success(): View
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $intent = $stripe->paymentIntents->retrieve(
            request('payment_intent'),
            []
        );
        // $intent['status'] == 'succeeded' or below
        // note to be considered forget your products from session after saving in DB based on the below block
        if (request('redirect_status') == 'succeeded') {
            // do stuff as saving orders in DB etc....
        }
        return view('stripe-accept', compact('intent'));
    }

    public function stripe(Request $request): View
    {
        $amount = $request->amount ?? 0;
        return view('stripe', compact('amount'));
    }
}
