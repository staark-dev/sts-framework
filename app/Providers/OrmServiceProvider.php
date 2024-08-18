<?php
namespace App\Providers;

use PDO;
use STS\core\{
    Container,
    Database\QueryBuilder,
    Database\ORM
};

class OrmServiceProvider {
    public static function register(Container $container) {
        // Înregistrarea PDO în container
        $container->singleton('db_conn_make', function($container) {
            $config = $container->make('config')->get('database.connections.mysql');

            $dsn = sprintf('%s:host=%s;dbname=%s;charset=%s',
                $config['driver'],
                $config['host'],
                $config['database'],
                $config['charset']
            );

            return new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        }, 100);

        // Înregistrarea QueryBuilder în container
        $container->singleton(QueryBuilder::class, function($container) {
            return new QueryBuilder($container->make('db_conn_make'), 'users'); // 'default_table' poate fi înlocuit cu ce tabel dorești
        }, 90);

        // Înregistrarea ORM în container
        $container->singleton('orm_db', function($container) {
            return new ORM('users', $container->make('db_conn_make'));
        }, 90);
    }

    public function boot()
    {
        // Coduri necesare la pornirea aplicației
    }
}
