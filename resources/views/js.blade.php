@php($vendor = ['vendor' => (int) config('cashier.vendor_id')])

<script src="https://cdn.paddle.com/paddle/paddle.js"></script>
<script type="text/javascript">
    @if (config('cashier.sandbox'))
        Paddle.Environment.set('sandbox');
    @endif

    Paddle.Setup(@json($vendor));
</script>
