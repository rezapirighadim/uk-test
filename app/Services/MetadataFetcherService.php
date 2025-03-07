<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class MetadataFetcherService
{
    /**
     * HTTP client timeout in seconds
     *
     * @var int
     */
    protected int $timeout = 10;

    /**
     * Fetch metadata from a URL
     *
     * @param string $url
     * @return array
     * @throws \Exception
     */
    public function fetch(string $url): array
    {
        try {
            $response = Http::timeout($this->timeout)->get($url);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch URL: HTTP status {$response->status()}");
            }

            $html = $response->body();
            return $this->extractMetadata($html);
        } catch (\Exception $e) {
            throw new Exception("Error fetching metadata: {$e->getMessage()}");
        }
    }

    /**
     * Extract metadata from HTML content
     *
     * @param string $html
     * @return array
     */
    protected function extractMetadata(string $html): array
    {
        $crawler = new Crawler($html);
        $metadata = [
            'title' => null,
            'description' => null,
        ];

        // Try to get title - first from Open Graph, then from regular title tag
        $metadata['title'] = $this->extractContent($crawler, 'meta[property="og:title"]', 'content')
            ?? $this->extractContent($crawler, 'title')
            ?? $this->extractContent($crawler, 'meta[name="twitter:title"]', 'content');

        // Try to get description - from various meta tags
        $metadata['description'] = $this->extractContent($crawler, 'meta[property="og:description"]', 'content')
            ?? $this->extractContent($crawler, 'meta[name="description"]', 'content')
            ?? $this->extractContent($crawler, 'meta[name="twitter:description"]', 'content');

        return $metadata;
    }

    /**
     * Extract content from an element
     *
     * @param Crawler $crawler
     * @param string $selector
     * @param string|null $attribute
     * @return string|null
     */
    protected function extractContent(Crawler $crawler, string $selector, ?string $attribute = null): ?string
    {
        try {
            $element = $crawler->filter($selector);

            if ($element->count() > 0) {
                if ($attribute) {
                    return trim($element->attr($attribute));
                }

                return trim($element->text());
            }
        } catch (\Exception $e) {
            // Ignore exceptions, just return null
        }

        return null;
    }
}
