<?php
/**
 * Sphinx Search
 *
 * @link        https://github.com/ripaclub/sphinxsearch
 * @copyright   Copyright (c) 2014, Leonardo Di Donato <leodidonato at gmail dot com>, Leonardo Grasso <me at leonardograsso dot com>
 * @license     http://opensource.org/licenses/BSD-2-Clause Simplified BSD License
 */
namespace SphinxSearchTest\Db\Adapter;

use Zend\ServiceManager\ServiceManager;
use Zend\ServiceManager\Config;
use \Zend\ServiceManager\Exception\ServiceNotCreatedException;
use SphinxSearch\Db\Adapter\AdapterServiceFactory;

class AdapterServiceFactoryTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Zend\ServiceManager\ServiceLocatorInterface
     */
    private $serviceManager;

    /**
     * Set up service manager and database configuration.
     *
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        $this->serviceManager = new ServiceManager(new Config(array(
            'factories' => array(
                'SphinxSearch\Db\Adapter\Adapter' => 'SphinxSearch\Db\Adapter\AdapterServiceFactory'
            ),
            'alias' => array(
                'sphinxql' => 'SphinxSearch\Db\Adapter\Adapter'
            )
        )));
        $this->serviceManager->setService('Config', array(
                'sphinxql' => array(
                    'driver' => 'pdo_mysql',
                ),
        ));
    }

    /**
     * @return array
     */
    public function providerValidService()
    {
        return array(
            array('SphinxSearch\Db\Adapter\Adapter')
        );
    }

    /**
     * @return array
     */
    public function providerInvalidService()
    {
        return array(
            array('SphinxSearch\Db\Adapter\Unknown'),
        );
    }

    /**
     * @param string $service
     * @dataProvider providerValidService
     * @testdox Instantiates an adapter
     */
    public function testValidService($service)
    {
        $actual = $this->serviceManager->get($service);
        $this->assertInstanceOf('Zend\Db\Adapter\Adapter', $actual);
    }

    /**
     * @param string $service
     * @dataProvider providerInvalidService
     * @expectedException \Zend\ServiceManager\Exception\ServiceNotFoundException
     * @testdox Does not instantiate an invalid/unknow adapter
     */
    public function testInvalidService($service)
    {
        $actual = $this->serviceManager->get($service);
        $this->assertInstanceOf('Zend\Db\Adapter\Adapter', $actual);
    }

   /**
    * @testdox Launch exception when driver is not supported
    */
   public function testUnsupportedDriver()
   {
       // testing Mysqli driver
       $mockDriver = $this->getMock('Zend\Db\Adapter\Driver\Pdo\Pdo', array('getDatabasePlatformName'), array(), '', false);
       $mockDriver->expects($this->any())->method('getDatabasePlatformName')->will($this->returnValue('NotMysql'));

       $sManager = new ServiceManager();
       $sManager->setService('Config', array(
               'sphinxql' => $mockDriver
       ));

       //Test exception by factory
       $factory = new AdapterServiceFactory();

       $this->setExpectedException('SphinxSearch\Db\Adapter\Exception\UnsupportedDriverException');
       $factory->createService($sManager);

   }

}