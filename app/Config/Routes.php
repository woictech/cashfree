<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('checkout', 'PaymentController::checkout');
$routes->post('create-order', 'PaymentController::createOrder');
$routes->get('payment-success', 'PaymentController::paymentSuccess');
$routes->get('payment-failure', 'PaymentController::paymentFailure');

