<?php

namespace Jackalope\Transport\DoctrineDBAL;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Jackalope\Factory;
use Jackalope\Test\FunctionalTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CachedClientTest extends FunctionalTestCase
{
    /**
     * @var Cache|MockObject
     */
    private $cacheMock;

    protected function getClient(Connection $conn)
    {
        $this->cacheMock = $this->createMock(Cache::class);

        return new CachedClient(new Factory(), $conn, ['nodes' => $this->cacheMock, 'meta' => $this->cacheMock]);
    }

    public function testArrayObjectIsConvertedToArray()
    {
        $namespaces = $this->transport->getNamespaces();
        self::assertIsArray($namespaces);
    }

    public function testCacheHit()
    {
        $cache = new \stdClass();
        $this->cacheMock->method('fetch')->with('nodes_3A+_2Ftest_2C+tests')->willReturn($cache);

        $this->assertSame($cache, $this->transport->getNode('/test'));
    }

    /**
     * The default key sanitizer keeps the cache key compatible with PSR16
     */
    public function testDefaultKeySanitizer()
    {
        $client = $this->getClient($this->getConnection());
        $reflection = new \ReflectionClass($client);
        $keySanitizerProperty = $reflection->getProperty('keySanitizer');
        $keySanitizerProperty->setAccessible(true);
        $defaultKeySanitizer = $keySanitizerProperty->getValue($client);

        $result = $defaultKeySanitizer(' :{}().@/"\\'); // not allowed PSR16 keys

        $this->assertEquals('+_3A_7B_7D_28_29|_40_2F_22_5C', $result);
    }

    public function testCustomkeySanitizer()
    {
        /** @var CachedClient $cachedClient */
        $cachedClient = $this->transport;
        //set a custom sanitizer that reveres the cachekey
        $cachedClient->setKeySanitizer(function ($cacheKey) {
            return strrev($cacheKey);
        });

        $first = true;
        $this->cacheMock
            ->method('fetch')
            ->with(self::callback(function ($arg) use (&$first) {
                self::assertEquals($first ? '}{:0:a :sepytedon' : 'sepyt_edon', $arg);
                $first = false;

                return true;
            }));

        $cachedClient->getNodeTypes();
    }
}
