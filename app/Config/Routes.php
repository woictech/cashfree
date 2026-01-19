<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('payment-success', 'CashfreeController::paymentSuccess');
$routes->post('offline-receipt', 'CashfreeController::offlinePayment');
$routes->post('cashfree/create-order', 'CashfreeController::createOrder');
$routes->get('cashfree/checkoutPage', 'CashfreeController::checkoutPage');

$routes->get('error-page', 'ErrorController::index');


