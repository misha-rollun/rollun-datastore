<?php
/**
 * Created by PhpStorm.
 * User: victorsecuring
 * Date: 28.10.16
 * Time: 1:16 PM
 */

namespace rolluncom\datastore\DataStore\Composite;

use Composer\IO\IOInterface;
use Interop\Container\ContainerInterface;
use rolluncom\installer\Install\InstallerAbstract;
use rolluncom\datastore\DataStore\Composite\Example\Store;
use rolluncom\datastore\DataStore\DbTable;
use rolluncom\datastore\TableGateway\DbSql\MultiInsertSql;
use Zend\Db\Adapter\AdapterInterface;
use rolluncom\datastore\TableGateway\TableManagerMysql as TableManager;
use Zend\Db\TableGateway\TableGateway;

class Installer extends InstallerAbstract
{
    /**
     *
     * @var AdapterInterface
     */
    private $dbAdapter;

    /**
     *
     *
     * Add to config:
     * <code>
     *    'services' => [
     *        'aliases' => [
     *            EavAbstractFactory::DB_SERVICE_NAME => getenv('APP_ENV') === 'prod' ? 'dbOnProduction' : 'local-db',
     *        ],
     *        'abstract_factories' => [
     *            EavAbstractFactory::class,
     *        ]
     *    ],
     * </code>
     * @param ContainerInterface $container
     * @param IOInterface $ioComposer
     */
    public function __construct(ContainerInterface $container, IOInterface $ioComposer)
    {
        parent::__construct($container, $ioComposer);
        $this->dbAdapter = $this->container->get('db');
    }

    public function uninstall()
    {
        if (constant('APP_ENV') !== 'dev') {
            $this->io->write('constant("APP_ENV") !== "dev" It has did nothing');
            exit;
        }
        $tableManager = new TableManager($this->dbAdapter);
        $tableManager->deleteTable(Store::IMAGE_TABLE_NAME);
        $tableManager->deleteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(Store::PRODUCT_TABLE_NAME);
        $tableManager->deleteTable(Store::CATEGORY_TABLE_NAME);

    }

    public function install()
    {
        if (constant('APP_ENV') === 'dev') {
            //develop only
            $tablesConfigDevelop = [
                TableManager::KEY_TABLES_CONFIGS => Store::$develop_tables_config
            ];
            $tableManager = new TableManager($this->dbAdapter, $tablesConfigDevelop);
            $tableManager->rewriteTable(Store::PRODUCT_TABLE_NAME);
            $tableManager->rewriteTable(Store::IMAGE_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_TABLE_NAME);
            $tableManager->rewriteTable(Store::CATEGORY_PRODUCT_TABLE_NAME);
            $this->addData();
        }
    }

    public function addData()
    {
        $data = array_merge(
            Store::$product,
            Store::$images,
            Store::$category,
            Store::$categoryProduct
        );

        foreach ($data as $key => $value) {
            $sql = new MultiInsertSql($this->dbAdapter, $key);
            $tableGateway = new TableGateway($key, $this->dbAdapter, null, null, $sql);
            $dataStore = new DbTable($tableGateway);
            $dataStore->create($value, true);
        }
    }
}