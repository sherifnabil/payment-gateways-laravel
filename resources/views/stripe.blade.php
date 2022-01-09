<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Accept a payment</title>
        <meta name="description" content="A demo of a payment on Stripe" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="/stripe-style.css">
        <script src="https://js.stripe.com/v3/"></script>
    </head>
    <body>
        <!-- Display a payment form -->
        <form id="payment-form">
            <div class="row">
                <h4>Your Total: {{ $amount }}</h4>
            </div>
            <div id="payment-element">
                <!--Stripe.js injects the Payment Element-->
            </div>
            <button id="submit">
                <div class="spinner hidden" id="spinner"></div>
                <span id="button-text">Pay now</span>
            </button>
            <div id="payment-message" class="hidden"></div>
        </form>
        <script>
            window.onload = function() {

                // This is your test publishable API key.
                const stripe = Stripe("{{ env('STRIPE_KEY') }}");

                // The items the customer wants to buy
                const items = [{ id: "xl-tshirt" }]; // any data want pass to backend
                let elements;

                initialize();
                checkStatus();

                document.querySelector("#payment-form").addEventListener("submit", handleSubmit);

                // Fetches a payment intent and captures the client secret
                async function initialize() {
                    const { clientSecret } = await fetch("{{ route('stripe-back') }}", {
                        method: "POST",
                        headers: { "Content-Type": "application/json" },
                        body: JSON.stringify({ items, amount: "{{ $amount }}" }),
                    }).then((r) => r.json());

                    elements = stripe.elements({ clientSecret });

                    const paymentElement = elements.create("payment");
                    paymentElement.mount("#payment-element");
                }

                async function handleSubmit(e) {
                    e.preventDefault();
                    setLoading(true);

                    const { error } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                        // Make sure to change this to your payment completion page
                            return_url: "{{ route('stripe.success') }}",
                        },
                    });

                    // This point will only be reached if there is an immediate error when
                    // confirming the payment. Otherwise, your customer will be redirected to
                    // your `return_url`. For some payment methods like iDEAL, your customer will
                    // be redirected to an intermediate site first to authorize the payment, then
                    // redirected to the `return_url`.
                    if (error.type === "card_error" || error.type === "validation_error") {
                        showMessage(error.message);
                    } else {
                        showMessage("An unexpected error occured.");
                    }

                    setLoading(false);
                }

                // Fetches the payment intent status after payment submission
                async function checkStatus() {
                    const clientSecret = new URLSearchParams(window.location.search).get("payment_intent_client_secret");

                    if (!clientSecret) {
                        return;
                    }

                    const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);

                    switch (paymentIntent.status) {
                        case "succeeded":
                            showMessage("Payment succeeded!");
                        break;
                        case "processing":
                            showMessage("Your payment is processing.");
                        break;
                        case "requires_payment_method":
                            showMessage("Your payment was not successful, please try again.");
                        break;
                        default:
                            showMessage("Something went wrong.");
                        break;
                    }
                }

                function showMessage(messageText) {
                    const messageContainer = document.querySelector("#payment-message");
                    messageContainer.classList.remove("hidden");
                    messageContainer.textContent = messageText;

                    setTimeout(function () {
                        messageContainer.classList.add("hidden");
                        messageText.textContent = "";
                    }, 4000);
                }

                // Show a spinner on payment submission
                function setLoading(isLoading) {
                    if (isLoading) {
                        // Disable the button and show a spinner
                        document.querySelector("#submit").disabled = true;
                        document.querySelector("#spinner").classList.remove("hidden");
                        document.querySelector("#button-text").classList.add("hidden");
                    } else {
                        document.querySelector("#submit").disabled = false;
                        document.querySelector("#spinner").classList.add("hidden");
                        document.querySelector("#button-text").classList.remove("hidden");
                    }
                }

            }
        </script>
    </body>
</html>
