<?php

namespace App\Http\Middleware;

use Closure;

class CashOnDeliveryOnly
{
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        if ($route) {
            $action = $route->getActionName();
            $parts = explode('@', $action);
            $controller = strtolower(class_basename($parts[0]));
            $method = strtolower($parts[1] ?? '');

            // Keep route names for existing templates, but disable all gateway callbacks
            // and entry points before their controllers can change payment/account state.
            $gateways = [
                'paypalcontroller', 'stripecontroller', 'stripepaymentcontroller',
                'publicsslcommerzpaymentcontroller', 'sslcommerzcontroller',
                'instamojocontroller', 'razorpaycontroller', 'paystackcontroller',
                'voguepaycontroller', 'payherecontroller', 'ngeniuscontroller',
                'iyzicocontroller', 'nagadcontroller', 'bkashcontroller',
                'aamarpaycontroller', 'authorizenetcontroller', 'paykucontroller',
                'mpesacontroller', 'flutterwavecontroller', 'payfastcontroller',
                'paytmcontroller', 'mercadopagocontroller', 'toyyibpaycontroller',
                'cinetpaycontroller', 'africanpaymentgatewaycontroller',
                'walletcontroller', 'offlinepaymentcontroller',
            ];
            $social = in_array($method, ['sociallogin', 'redirecttoprovider', 'handleprovidercallback'], true);
            $manual = $controller === 'paymentcontroller' && $method !== 'cashondelivery';
            $walletConversion = $controller === 'clubpointcontroller' && $method === 'convert_into_wallet';
            $packagePayment = in_array($controller, ['customerpackagecontroller', 'sellerpackagecontroller'], true)
                && in_array($method, ['purchase_package', 'purchase_package_offline', 'purchase_payment_done'], true);
            $offlineOrderPayment = $controller === 'manualpaymentmethodcontroller'
                && in_array($method, ['show_payment_modal', 'submit_offline_payment'], true);
            abort_if(in_array($controller, $gateways, true) || $social || $manual || $walletConversion || $packagePayment || $offlineOrderPayment, 404);
        }

        return $next($request);
    }
}
