<?php
$seller = array_filter([
    'seller' => (int) config('cashier.seller_id'),
    'pwAuth' => (int) config('cashier.retain_key'),
]);

if (isset($seller['pwAuth']) && Auth::check() && $customer = Auth::user()->customer) {
    $seller['pwCustomer'] = ['id' => $customer->paddle_id];
}
?>

<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>

@if (config('cashier.sandbox'))
    <script type="text/javascript">
        Paddle.Environment.set('sandbox');
    </script>
@endif

<script type="text/javascript">
    Paddle.Setup(@json($seller));
</script>
