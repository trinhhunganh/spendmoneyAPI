<?php

namespace Phalcon\Test\Annotations\Adapter;

use Phalcon\Annotations\Adapter\Memcached;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * MemcachedTest
 * Tests for class Memcached
 *
 * @copyright (c) 2011-2015 Phalcon Team
 * @package   Phalcon\Test\Annotations\Adapter
 * @author    Ilya Gusev <mail@igusev.ru>
 * @link      http://phalconphp.com/
 *
 * The contents of this file are subject to the New BSD License that is
 * bundled with this package in the file docs/LICENSE.txt
 *
 * If you did not receive a copy of the license and are unable to obtain it
 * through the world-wide-web, please send an email to license@phalconphp.com
 * so that we can send you a copy immediately.
 */
class MemcachedTest extends TestCase
{

    public function dataConstructor()
    {
        return array(
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 23
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 23,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'prefix' => 'test_'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 8600,
                    'prefix' => 'test_'
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'randomValue' => 'test_'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'randomValue' => 'test_',
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    123 => 'test_'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    123 => 'test_',
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 24,
                    'prefix' => 'test_'
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 24,
                    'prefix' => 'test_'
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'weight' => 1
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
            array(
                array(
                    'host' => '127.0.0.1',
                ),
                array(
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 1,
                    'lifetime' => 8600,
                    'prefix' => ''
                )
            ),
        );
    }

    public function dataKey()
    {
        return array(
            array(
                'key1'
            ),
            array(
                1
            ),
            array(
                '_key1'
            )
        );
    }

    public function dataReadWrite()
    {
        return array(
            array('test1', 'data1'),
            array('test1', (object) array('key' => 'value')),
            array('test1', array('key' => 'value')),
            array('test1', null)
        );
    }

    /**
     * @expectedException           \Phalcon\Mvc\Model\Exception
     * @expectedExceptionMessage    No host given in options
     */
    public function testConstructorException()
    {
        $object = new Memcached(array('lifetime' => 23, 'prefix' => ''));
    }

    /**
     * @expectedException           \Phalcon\Mvc\Model\Exception
     * @expectedExceptionMessage    No configuration given
     */
    public function testConstructorException2()
    {
        $object = new Memcached(1);
    }

    /**
     * @dataProvider dataConstructor
     */
    public function testConstructor($options, $expected)
    {
        $object = new Memcached($options);
        $reflectedProperty = new ReflectionProperty(get_class($object), 'options');
        $reflectedProperty->setAccessible(true);
        $this->assertEquals($expected, $reflectedProperty->getValue($object));
    }

    /**
     * @dataProvider dataKey
     */
    public function testPrepareKey($key)
    {
        $object = new Memcached(array('host' => '127.0.0.1'));
        $reflectedMethod = new ReflectionMethod(get_class($object), 'prepareKey');
        $reflectedMethod->setAccessible(true);
        $this->assertEquals($key, $reflectedMethod->invoke($object, $key));
    }

    public function testGetCacheBackend()
    {
        $object = new Memcached(array('host' => '127.0.0.1'));
        $mock = $this->getMock('\Phalcon\Cache\Backend\Libmemcached', array(), array(), '', false);

        $reflectedProperty = new ReflectionProperty(get_class($object), 'memcached');
        $reflectedProperty->setAccessible(true);
        $reflectedProperty->setValue($object, $mock);

        $reflectedMethod = new ReflectionMethod(get_class($object), 'getCacheBackend');
        $reflectedMethod->setAccessible(true);
        $this->assertInstanceOf('\Phalcon\Cache\Backend\Libmemcached', $reflectedMethod->invoke($object));
    }


    /**
     *
     * @requires extension memcached
     */
    public function testGetCacheBackend2()
    {
        $object = new Memcached(array('host' => '127.0.0.1'));

        $reflectedMethod = new ReflectionMethod(get_class($object), 'getCacheBackend');
        $reflectedMethod->setAccessible(true);
        $this->assertInstanceOf('\Phalcon\Cache\Backend\Libmemcached', $reflectedMethod->invoke($object));
    }

    /**
     *
     * @dataProvider dataReadWrite
     * @requires extension memcached
     */
    public function testReadWriteWithoutPrefix($key, $data)
    {
        $object = new Memcached(array('host' => '127.0.0.1'));

        $object->write($key, $data);

        $this->assertEquals($data, $object->read($key));
    }

    /**
     *
     * @dataProvider dataReadWrite
     * @requires extension memcached
     */
    public function testReadWriteWithPrefix($key, $data)
    {
        $object = new Memcached(array('host' => '127.0.0.1', 'prefix' => 'test_'));

        $object->write($key, $data);

        $this->assertEquals($data, $object->read($key));
    }

    /**
     *
     * @requires extension memcached
     */
    //    public function testReadWriteWithoutPrefix2($key, $data)
    //    {
    //        $object = new \Phalcon\Annotations\Adapter\Memcached(array('host' => '127.0.0.1');
    //
    //        $object->write($key, $data);
    //
    //        $this->assertEquals($data, $object->read($key));
    //
    //        $reflectedMethod = new \ReflectionMethod(get_class($object), 'getCacheBackend');
    //        $reflectedMethod->setAccessible(true);
    //        $this->assertInstanceOf('\Phalcon\Cache\Backend\Libmemcached', $reflectedMethod->invoke($object));
    //    }

    public function testHasDefaultPort()
    {
        $this->assertClassHasStaticAttribute('defaultPort', '\Phalcon\Annotations\Adapter\Memcached');
    }

    public function testHasDefaultWeight()
    {
        $this->assertClassHasStaticAttribute('defaultWeight', '\Phalcon\Annotations\Adapter\Memcached');
    }

    public function testHasMemcached()
    {
        $this->assertClassHasAttribute('memcached', '\Phalcon\Annotations\Adapter\Memcached');
    }
}
