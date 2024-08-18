<?php
return [

/*
|--------------------------------------------------------------------------
| Default Session Driver
|--------------------------------------------------------------------------
|
| Aceasta opțiune controlează driverul implicit de sesiuni folosit de
| aplicația ta. Este suportat un număr variat de drivere, precum
| "file", "cookie", "database", "redis", și "array".
|
*/

'driver' => env('SESSION_DRIVER', 'file'),

/*
|--------------------------------------------------------------------------
| Session Lifetime
|--------------------------------------------------------------------------
|
| Aici poți specifica durata de viață a sesiunilor, în minute. Dacă dorești
| ca sesiunile să expire la închiderea browserului, setează această valoare
| la 0.
|
*/

'lifetime' => env('SESSION_LIFETIME', 120),

/*
|--------------------------------------------------------------------------
| Session File Location
|--------------------------------------------------------------------------
|
| Când folosești driverul "file", poți specifica calea unde vor fi stocate
| fișierele de sesiune.
|
*/

'files' => storage_path('framework/sessions'),

/*
|--------------------------------------------------------------------------
| Session Cookie Name
|--------------------------------------------------------------------------
|
| Poți schimba numele cookie-ului care este folosit pentru identificarea
| unei sesiuni în browserul utilizatorului.
|
*/

'cookie' => env('SESSION_COOKIE', 'myapp_session'),

/*
|--------------------------------------------------------------------------
| Encrypt Session Data
|--------------------------------------------------------------------------
|
| Dacă dorești ca toate datele din sesiune să fie criptate, poți seta
| această opțiune la true.
|
*/

'encrypt' => false,

/*
|--------------------------------------------------------------------------
| Session Database Table
|--------------------------------------------------------------------------
|
| Dacă folosești driverul "database", specifică numele tabelului unde
| vor fi stocate sesiunile.
|
*/

'table' => 'sessions',

/*
|--------------------------------------------------------------------------
| Session Cache Store
|--------------------------------------------------------------------------
|
| Când folosești driverul "cache" pentru sesiuni, specifică care store de
| cache ar trebui utilizat pentru stocarea datelor sesiunii.
|
*/

'store' => env('SESSION_STORE', 'redis'),

/*
|--------------------------------------------------------------------------
| Session Garbage Collection Probability
|--------------------------------------------------------------------------
|
| Poți specifica probabilitatea ca procesul de colectare a sesiunilor
| expirate să fie declanșat la fiecare cerere. Valoarea este un array cu
| două elemente, care controlează "probabilitatea 1 / X".
|
*/

'lottery' => [2, 100],

/*
|--------------------------------------------------------------------------
| Session Handler Collection
|--------------------------------------------------------------------------
|
| Poți specifica probabilitatea ca procesul de colectare a sesiunilor
| expirate să fie declanșat la fiecare cerere. Valoarea este un array cu
| două elemente, care controlează "probabilitatea 1 / X".
|
*/
'handler' => \SessionHandler::class,  // Sau un handler personalizat
];