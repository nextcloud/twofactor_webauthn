<?php
/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\TwoFactorWebauthn\Controller\SettingsController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
    'routes' => [
        [
            'name' => 'settings#startRegister',
            'url' => '/settings/startregister',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#finishRegister',
            'url' => '/settings/finishregister',
            'verb' => 'POST'
        ],
        [
            'name' => 'settings#remove',
            'url' => '/settings/remove',
            'verb' => 'POST'
        ],
    ]
];
