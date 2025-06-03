<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('checkout', 'PaymentController::checkout');
$routes->post('create-order', 'PaymentController::createOrder');
$routes->get('payment-success', 'CashfreeController::paymentSuccess');
$routes->get('payment-failure', 'CashfreeController::paymentFailure');


$routes->get('cashfree/create-order', 'CashfreeController::createOrder');
$routes->get('cashfree/checkoutPage', 'CashfreeController::checkoutPage');
$routes->get('cashfree/verify/(:any)', 'CashfreeController::verifyOrder/$1');

