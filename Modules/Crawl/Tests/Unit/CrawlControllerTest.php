<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Crawl\Service\CrawlWikipedia;

class CrawlControllerTest extends TestCase
{

    private $mockCorrectUrl;
    private $mockIncorrectUrl;
    private $crawlUrl;
    private $crawlForceUrl;
    private $mockData;
    private $mockService;

    public function setUp(): void
    {
        parent::setUp();
        $this->mockCorrectUrl = 'https://en.wikipedia.org/wiki/Women%27s_high_jump_world_record_progression#References';
        $this->mockIncorrectUrl = 'https://incorrect.en.wikipedia.org/wiki/Women%27s_high_jump_world_record_progression#References';
        $this->crawlUrl = '/api/v1/crawl';
        $this->crawlForceUrl = '/api/v1/crawl-force';
        $this->mockData = ['data' => []];
        $this->mockService = $this->createMock(CrawlWikipedia::class);
    }


    public function testCrawlData()
    {
        $mockService = $this->createMock(CrawlWikipedia::class);
        $this->app->instance(CrawlWikipedia::class, $mockService);

        $mockService->expects($this->once())
            ->method('crawl')
            ->with($this->mockCorrectUrl)
            ->willReturn($this->mockData);

        $response = $this->json('POST', $this->crawlUrl, ['url' => $this->mockCorrectUrl]);

        $response->assertStatus(200)
            ->assertJson($this->mockData);
    }

    public function testForceCrawlData()
    {
        $this->app->instance(CrawlWikipedia::class, $this->mockService);

        $this->mockService->expects($this->once())
            ->method('crawl')
            ->with($this->mockCorrectUrl, true)
            ->willReturn($this->mockData);

        $response = $this->json('POST', $this->crawlForceUrl, ['url' => $this->mockCorrectUrl]);

        $response->assertStatus(200)
            ->assertJson($this->mockData);
    }

    public function testCrawlDataIncorrectUrl()
    {
        $response = $this->json('POST', $this->crawlUrl, ['url' => $this->mockIncorrectUrl]);
        $response->assertStatus(500);
        $response = $this->json('POST', $this->crawlForceUrl, ['url' => $this->mockIncorrectUrl]);
        $response->assertStatus(500);
    }

    public function testCrawlDataWithServiceFailure()
    {
        $this->app->instance(CrawlWikipedia::class, $this->mockService);

        $this->mockService->expects($this->once())
            ->method('crawl')
            ->will($this->throwException(new \Exception('An error occurred')));
        $response = $this->json('POST', $this->crawlUrl, ['url' => $this->mockCorrectUrl]);

        $response->assertStatus(500);
    }
    public function testCrawlDataWithEmptyResponse()
    {
        $this->app->instance(CrawlWikipedia::class, $this->mockService);

        $this->mockService->expects($this->once())
            ->method('crawl')
            ->willReturn([]);

        $response = $this->json('POST', $this->crawlUrl, ['url' => $this->mockCorrectUrl]);

        $response->assertStatus(200)
            ->assertJson([]);
    }
}
