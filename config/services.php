<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Stripe, Mailgun, Mandrill, and others. This file provides a sane
    | default location for this type of information, allowing packages
    | to have a conventional place to find your various credentials.
    |
    */

   'github' => [
        'client_id' => env('GITHUB_ID'),
        'client_secret' => env('GITHUB_SECRET'),
        'redirect' => env('GITHUB_CALLBACK_URL'),
        'token' => env('GITHUB_TOKEN'),
    ],

    'google' => [
        'client_id' => env('GOOGLE_ID'),
        'client_secret' => env('GOOGLE_SECRET'),
        'redirect' => env('GOOGLE_CALLBACK_URL'),
    ],

    'mailgun' => [
        'domain' => env('MAIL_USERNAME'),
        'secret' => env('MAIL_PASSWORD'),
    ],

    'mandrill' => [
        'secret' => env('MAIL_PASSWORD'),
    ],

    'ses' => [
        'key'    => env('MAIL_USERNAME'),
        'secret' => env('MAIL_PASSWORD'),
        'region' => 'us-east-1',
    ],

    'sparkpost' => [
        'secret' => env('MAIL_PASSWORD'),
    ],

];
