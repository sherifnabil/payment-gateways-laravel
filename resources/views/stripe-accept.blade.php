<html>
    <head>
        <title>Thanks for your order!</title>
    </head>
    <body>
        <h1>Thanks for your order!</h1>
        <p>
            We appreciate your business!
            If you have any questions, please email
            <br><br>
            {{-- {{ request()->all() }} --}}
            {{-- @dd(request()->all(), $intent) --}}
            {{ $intent }}
        </p>
    </body>
</html>
