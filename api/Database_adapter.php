<?php
namespace API;

use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Db\Adapter\Pdo\Postgresql as PdoPostgresql;
use Phalcon\Db\Adapter\Pdo\Sqlite as PdoSqlite;
use Phalcon\Db\Adapter\Pdo\Oracle as PdoOracle;
use Phalcon\Http\Response;

class Database_adapter
{
    function getDBAdapter($configuration)
    {
        $adapter = $configuration->database->adapter;
        switch ($adapter) {
            case 'mysql':
                return new PdoMysql(
                    (array)$configuration->database->config_params
                );
                break;
            case 'postgresql':
                return new PdoPostgresql(
                    (array)$configuration->database->config_params
                );
                break;
            case 'sqlite':
                return new PdoSqlite(
                    (array)$configuration->database->config_params
                );
                break;
            case 'oracle':
                return new PdoOracle(
                    (array)$configuration->database->config_params
                );
                break;  
            default:
                return new PdoMysql(
                    (array)$configuration->database->config_params
                );
                break;
        }
    }
}