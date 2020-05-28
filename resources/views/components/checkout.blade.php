<div {{ $attributes->merge(['class' => $id]) }}></div>
<script type="text/javascript">
    Paddle.Checkout.open(@json($options()));
</script>
