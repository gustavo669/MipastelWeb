<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cuenta de administrador inicial
    |--------------------------------------------------------------------------
    |
    | La usa DatabaseSeeder solo cuando NO existe ese correo. Si ADMIN_PASSWORD
    | está vacío, el seeder genera una contraseña aleatoria y la muestra una vez.
    |
    */

    'admin_email' => env('ADMIN_EMAIL', 'admin@mipastel.test'),

    'admin_password' => env('ADMIN_PASSWORD'),

];
