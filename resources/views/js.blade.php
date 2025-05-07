<?php

$seller = array_filter([
    'pwAuth' => (int) config('cashier.retain_key'),
]);

if (config('cashier.client_side_token')) {
    $seller['token'] = config('cashier.client_side_token');
} elseif (config('cashier.seller_id')) {
    $seller['seller'] = (int) config('cashier.seller_id');
}

if (isset($seller['pwAuth']) && Auth::check() && $customer = Auth::user()->customer) {
    $seller['pwCustomer'] = ['id' => $customer->paddle_id];
}

$nonce = $nonce ?? '';
?>

<script src="https://cdn.paddle.com/paddle/v2/paddle.js" @if ($nonce) nonce="{{ $nonce }}" @endif></script>

@if (config('cashier.sandbox'))
    <script type="text/javascript" @if ($nonce) nonce="{{ $nonce }}" @endif>
        Paddle.Environment.set('sandbox');
    </script>
@endif

<script type="text/javascript" @if ($nonce) nonce="{{ $nonce }}" @endif>
    Paddle.Initialize(@json($seller));
</script>
