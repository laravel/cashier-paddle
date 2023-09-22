<?php
$seller = array_filter([
    'seller' => (int) config('cashier.seller_id'),
    'pwAuth' => (int) config('cashier.retain_key'),
]);
?>

<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script type="text/javascript">
    @if (config('cashier.sandbox'))
        Paddle.Environment.set('sandbox');
    @endif

    Paddle.Setup(@json($seller));
</script>
