<?php
$seller = array_filter([
    'seller' => (int) config('cashier.seller_id'),
    'pwAuth' => (int) config('cashier.retain_key'),
]);
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
