<?php

namespace Modules\Crawl\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Crawl\Service\CrawlWikipedia;
use Modules\Crawl\Http\Requests\WikipediaRequest;

class CrawlController extends Controller
{

    private $crawlService;

    public function __construct(CrawlWikipedia $crawlService)
    {
        $this->crawlService = $crawlService;
    }

    /**
     * Crawl function
     *
     * @param  WikipediaRequest $request
     * @return JsonResponse
     */
    public function crawlData(WikipediaRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['url'])) {
            $data = $this->crawlService->crawl($data['url']);
            return response()->json($data);
        }

        return response()->json(null, 400);
    }
    
    /**
     * Crawl the data forcefully.
     *
     * @param  WikipediaRequest $request
     * @return JsonResponse
     */
    public function forceCrawlData(WikipediaRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (isset($data['url'])) {
            $data = $this->crawlService->crawl($data['url'], true);
            return response()->json($data);
        }

        return response()->json(null, 400);
    }

}
