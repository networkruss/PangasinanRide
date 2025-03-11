<?php

use Config\Services;
use CodeIgniter\Router\RouteCollection;

$routes = Services::routes();

// Load the system's routing file first, so that the app and ENVIRONMENT
// can override as needed.
if (file_exists(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(true);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

/**
 * @var RouteCollection $routes
 */
$routes->group('/api/auth', ['filter' => 'cors'], function ($routes) {
    $routes->get('users', 'UserAuth::index');             // GET all users
    $routes->post('verified-ok/(:segment)', 'UserAuth::removeVerificationToken/$1');             // GET all users
    $routes->get('is-verified/(:segment)', 'UserAuth::isVerified/$1');             // GET all users
    $routes->get('user/(:segment)', 'UserAuth::show/$1'); // GET user by UUID
    $routes->post('create-provider', 'UserAuth::createProvider');            // POST create new user
    $routes->post('create-client', 'UserAuth::createClient');            // POST create new user
    $routes->post('change-password', 'UserAuth::changePassword');            // POST create new user
    $routes->post('user/(:segment)', 'UserAuth::update/$1'); // PUT update user by UUID
    $routes->delete('user/(:segment)', 'UserAuth::delete/$1'); // DELETE user by UUID
    $routes->post('login', 'UserAuth::login');
    $routes->post('register/admin', 'UserAuth::registerAdmin'); // For admin registration
});

$routes->group('/api/user', ['filter' => 'cors'], function ($routes) {
    $routes->get('user-check/(:segment)', 'UserInfo::verifyUserExists/$1');            // GET all user info
    $routes->get('user-info-all', 'UserInfo::getAllUsersWithInfo');            // GET all user info
    $routes->get('user-info-verfied/(:segment)', 'UserInfo::getUserInfoVerified/$1');            // GET all user info
    $routes->get('user-info/(:segment)', 'UserInfo::show/$1'); // GET user info by UUID
    $routes->get('get-user-info/(:segment)', 'UserInfo::show/$1'); // GET user info by UUID
    $routes->post('user-info', 'UserInfo::saveUserInfo');          // POST create new user info or update existing
    $routes->delete('user-info/(:segment)', 'UserInfo::delete/$1'); // DELETE user info by UUID    
    $routes->get('user-info/verify/(:segment)', 'UserInfo::verifyUser/$1');
    $routes->get('user-info/decline/(:segment)', 'UserInfo::declineUser/$1');

    $routes->get('addresses', 'UserAddress::index');               // GET all addresses
    $routes->get('address/(:segment)', 'UserAddress::show/$1');     // GET address by UUID
    $routes->post('address', 'UserAddress::saveAddress');           // POST create or update address
    $routes->delete('address/(:segment)', 'UserAddress::delete/$1'); // DELETE address by UUID
});

$routes->group('/api/feedback', ['filter' => 'cors'], function ($routes) {
    $routes->post('feedback-to-client', 'ClientFeedbackController::sendFeedbackToClient');
    $routes->post('feedback', 'FeedbackController::create');
    $routes->get('feedback', 'FeedbackController::index');
    $routes->post('already-feedback', 'FeedbackController::alreadyFeedback');
    $routes->get('feedback/(:num)', 'FeedbackController::getAllFeedbackByCarId/$1');
    $routes->put('feedback/(:num)', 'FeedbackController::update/$1');
    $routes->patch('feedback/(:num)', 'FeedbackController::update/$1');
    $routes->delete('feedback/(:num)', 'FeedbackController::delete/$1');
});

$routes->group('/api/cars', ['filter' => 'cors'], function ($routes) {
    // Car Listings routes
    $routes->get('listings', 'CarListings::index');                // GET all car listings
    $routes->get('listing/(:segment)', 'CarListings::show/$1');     // GET car listing by ID
    $routes->get('owner/(:segment)', 'CarListings::showByUUID/$1');     // GET car listing by ID
    $routes->post('listing', 'CarListings::saveListing');           // POST create or update car listing
    $routes->post('update-listing', 'CarListings::updateListing');           // POST create or update car listing
    $routes->delete('listing/(:segment)', 'CarListings::delete/$1'); // DELETE car listing by ID
    $routes->post('listing/approve/(:num)', 'CarListings::approveListing/$1'); // Approve a car listing
    $routes->post('listing/decline/(:num)', 'CarListings::declineListing/$1'); // Decline a car listing
});

$routes->group('/api/bookings', ['filter' => 'cors'], function ($routes) {
    $routes->get('all', 'Booking::index');                             // GET all bookings
    $routes->get('booking/(:segment)', 'Booking::show/$1');             // GET booking by ID
    $routes->get('owner/(:segment)/bookings', 'Booking::showByOwnerUUID/$1'); // GET bookings by owner UUID
    $routes->get('booker/(:segment)/bookings', 'Booking::showByBookerUUID/$1'); // GET bookings by booker UUID
    $routes->post('save', 'Booking::saveBooking');                      // POST create or update booking
    $routes->delete('booking/(:segment)', 'Booking::delete/$1');        // DELETE booking by ID
    $routes->post('approve/(:num)', 'Booking::approveBooking/$1');
    $routes->post('decline/(:num)', 'Booking::declineBooking/$1');
    $routes->post('complete/(:num)', 'Booking::markBookingCompleted/$1');
    $routes->post('cancel/(:num)', 'Booking::cancelBooking/$1');
});

$routes->group('/api/transactions', ['filter' => 'cors'], function ($routes) {
    $routes->get('all', 'Transactions::index');                          // GET all transactions
    $routes->get('transaction/(:segment)', 'Transactions::show/$1');      // GET transaction by ID
    $routes->get('owner/(:segment)/transactions', 'Transactions::showByOwnerUUID/$1'); // GET transactions by owner UUID
    $routes->get('booker/(:segment)/transactions', 'Transactions::showByBookerUUID/$1'); // GET transactions by booker UUID
    $routes->post('save', 'Transactions::saveTransaction');               // POST create or update transaction
    $routes->post('refund/(:segment)', 'Transactions::refund/$1');        // POST refund transaction by ID
    $routes->delete('transaction/(:segment)', 'Transactions::delete/$1'); // DELETE transaction by ID
});

$routes->group('/api/notifications', ['filter' => 'cors'], function ($routes) {
    $routes->get('user/(:segment)', 'Notifications::showByUUID/$1'); // GET notifications by user UUID
    $routes->post('mark-as-read/(:num)', 'Notifications::markAsRead/$1');
});

$routes->get('/verify-email', 'UserAuth::verifyEmail');

if (file_exists(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
