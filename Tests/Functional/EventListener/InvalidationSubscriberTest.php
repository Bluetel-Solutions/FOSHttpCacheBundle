<?php

/*
 * This file is part of the FOSHttpCacheBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\HttpCacheBundle\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InvalidationSubscriberTest extends WebTestCase
{
    public function testInvalidateRoute()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('supports')->andReturn(true)
            ->shouldReceive('invalidateRoute')->once()->with('test_noncached', array())
            ->shouldReceive('invalidateRoute')->once()->with('test_cached', array('id' => 'myhardcodedid'))
            ->shouldReceive('invalidateRoute')->once()->with('tag_one', array('id' => '42'))
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/invalidate/route/42');
    }

    /**
     * @dataProvider getStatusCodesThatTriggerInvalidation
     */
    public function testInvalidatePath($statusCode)
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('supports')->andReturn(true)
            ->shouldReceive('invalidatePath')->once()->with('/cached')
            ->shouldReceive('invalidatePath')->once()->with(
                sprintf('/invalidate/path/%s', $statusCode)
            )
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', sprintf('/invalidate/path/%s', $statusCode));
    }

    public function testErrorIsNotInvalidated()
    {
        $client = static::createClient();

        $client->getContainer()->mock(
            'fos_http_cache.cache_manager',
            '\FOS\HttpCacheBundle\CacheManager'
        )
            ->shouldReceive('supports')->andReturn(true)
            ->shouldReceive('invalidatePath')->never()
            ->shouldReceive('flush')->once()
        ;

        $client->request('POST', '/invalidate/error');
    }

    public function getStatusCodesThatTriggerInvalidation()
    {
        return array(array(200), array(204), array(302));
    }

    protected function tearDown()
    {
        static::createClient()->getContainer()->unmock('fos_http_cache.cache_manager');
    }
}
