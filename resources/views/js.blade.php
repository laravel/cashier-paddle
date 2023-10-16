@php($vendor = ['vendor' => (int) config('cashier.vendor_id')])

<script src="https://cdn.paddle.com/paddle/paddle.js"></script>

@if (config('cashier.sandbox'))
    <script type="text/javascript">
        Paddle.Environment.set('sandbox');
    </script>
@endif

<script type="text/javascript">
    Paddle.Setup(@json($vendor));
</script>
