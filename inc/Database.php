<?php
namespace Inc;
use PDO;
class Database
{
    private static ?PDO $connection = null;
    private static string $host   = '127.0.0.1';
    private static string $dbname = 'campus_food_ordering_system';
    private static string $user   = 'root';
    private static string $pass   = 'root123';

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=utf8",
                self::$user,
                self::$pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        }

        return self::$connection;
    }
}

