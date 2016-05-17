<?php

require_once 'config.php';
require_once('Constantes.php');

class Database {
    private static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            switch (DB_TYPE_SGBD) {
                case 'mysql':
                    try {
                        self::$instance = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                        self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                    break;
                case 'postgres':
                    try {
                        self::$instance = new PDO('pgsql:dbname='.DB_NAME.';host='.DB_HOST, DB_USER,DB_PASS);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                        self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                    break;
                case 'msssql_dblib':
                    try {
                        self::$instance = new PDO('dblib:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                        self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                    break;
                case 'msssql_srv':
                    try {
                        self::$instance = new PDO('sqlsrv:server=' . DB_HOST . ';Database=' . DB_NAME, DB_USER, DB_PASS);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                        self::$instance->query('SET TEXTSIZE 2147483647');
                    } catch (PDOException $e) {
                        echo "Error!: " . $e->getMessage() . "\n";
                    }
                    break;
                default:
                    try {
                        self::$instance = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                        self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
                        self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
                    } catch (PDOException $e) {
                        echo $e->getMessage();
                    }
                    break;
            }

        }

        return self::$instance;
    }

    public static function prepare($sql)
    {
        return self::getInstance()->prepare($sql);
    }

    public static function query($sql)
    {
        return self::getInstance()->query($sql);
    }
}