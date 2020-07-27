<?php

namespace Laravel\Paddle\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @see https://developer.paddle.com/webhook-reference/verifying-webhooks
 */
class VerifyWebhookSignature
{
    const SIGNATURE_KEY = 'p_signature';

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response
     *
     * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function handle(Request $request, Closure $next)
    {
        $fields = $this->extractFields($request);
        $signature = $request->get(self::SIGNATURE_KEY);

        if ($this->isInvalidSignature($fields, $signature)) {
            throw new AccessDeniedHttpException('Invalid webhook signature.');
        }

        return $next($request);
    }

    /**
     * Extract fields from request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function extractFields(Request $request)
    {
        $fields = $request->except(self::SIGNATURE_KEY);

        ksort($fields);

        foreach ($fields as $k => $v) {
            if (! in_array(gettype($v), ['object', 'array'])) {
                $fields[$k] = "$v";
            }
        }

        return $fields;
    }

    /**
     * Validate signature.
     *
     * @param  array  $fields
     * @param  string  $signature
     * @return bool
     */
    protected function isInvalidSignature(array $fields, $signature)
    {
        return openssl_verify(
            serialize($fields),
            base64_decode($signature),
            openssl_get_publickey(config('cashier.public_key')),
            OPENSSL_ALGO_SHA1
        ) !== 1;
    }
}
