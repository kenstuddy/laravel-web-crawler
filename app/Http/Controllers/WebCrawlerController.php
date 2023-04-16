<?php

namespace App\Http\Controllers;

use DOMXPath;
use http\Exception\RuntimeException;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use DOMDocument;
use Illuminate\Http\Response;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WebCrawlerController extends Controller
{
    /**
     * @var array The URLs that have been visited during the crawl
     */
    private $visitedPages = [];

    /**
     * @var array The internal links found during the crawl
     */
    private $internalLinks = [];

    /**
     * @var array The external links found during the crawl
     */
    private $externalLinks = [];

    /**
     * @var array The image URLs found during the crawl
     */
    private $images = [];

    /**
     * @var string The entry point URL for the crawl
     */
    private $entryPoint;

    /**
     * @var array The page load times recorded during the crawl
     */
    private $pageLoadTimes = [];

    /**
     * @var array The word counts for each page during the crawl
     */
    private $wordCounts = [];

    /**
     * @var array The title lengths for each page during the crawl
     */
    private $titleLengths = [];

    /**
     * @var array The HTTP status codes for each page during the crawl
     */
    private $pageStatusCodes = [];

    /**
     * @var bool Whether to count subdomains as internal links
     */
    private $countSubdomainsAsInternal;

    /**
     * @var bool Whether to crawl subdomains
     */
    private $crawlSubdomains;

    /**
     * @var bool Download CSV report of the results
     */
    private $downloadCsvReport;

    /**
     * @var int The maximum number of pages to crawl
     */
    private $maxPages;

    /**
     * @var string The current date time string
     */
    private $dateTimeString;

    /**
     * Handles the form submission and starts the web crawl
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|ResponseFactory|Factory|Application|RedirectResponse|Response|Redirector|View
     */
    public function handleFormSubmit(Request $request) : \Illuminate\Contracts\Foundation\Application|ResponseFactory|Factory|Application|RedirectResponse|Response|Redirector|View
    {
        $validator = Validator::make($request->all(), [
            'entry_point' => 'required|url',
            'max_pages' => 'numeric'
        ]);
        if ($validator->fails()) {
            return redirect('/')->withErrors($validator);
        }
        $this->countSubdomainsAsInternal = $request->input('count_subdomains_as_internal') === 'true';
        $this->entryPoint = $request->input('entry_point');
        $this->crawlSubdomains = $request->input('crawl_subdomains') === 'true';
        $this->downloadCsvReport = $request->input('download_csv_report') === 'true';
        $this->maxPages = $request->input('max_pages', 6);
        return $this->displayResults();
    }

    /**
     * Crawls the website starting from the entry point
     *
     * @param string $url The entry point URL to start the crawl
     * @param int $maxPages The maximum number of pages to crawl
     */
    private function crawl(string $url, int $maxPages): void
    {
        $queue = [$url];
        $entryPointHost = parse_url($this->entryPoint, PHP_URL_HOST);
        while (!empty($queue) && count($this->visitedPages) < $maxPages) {
            $currentUrl = array_shift($queue);

            //Check if we have already visited the current URL
            if (in_array($currentUrl, $this->visitedPages)) {
                continue;
            }

            //Check if the current URL is not empty and is a valid URL
            if (empty($currentUrl) || !filter_var($currentUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            //Benchmark website load time
            $startTime = microtime(true);
            $content = @file_get_contents($currentUrl);
            $endTime = microtime(true);

            if ($content === false) {
                continue;
            }

            $this->visitedPages[] = $currentUrl;
            $this->pageLoadTimes[] = $endTime - $startTime;

            //Create a DOMDocument object to load the HTML of the current page
            $dom = new DOMDocument();
            @$dom->loadHTML($content);

            //Extract images
            $images = $dom->getElementsByTagName('img');
            foreach ($images as $image) {
                $this->images[] = $image->getAttribute('src');
            }

            //Extract internal and external links
            $xpath = new DOMXPath($dom);
            $links = $xpath->query("//a[@href] | //img[@src] | //script[@src]| //link[@href]");

            foreach ($links as $link) {
                if ($link->hasAttribute('href')) {
                    $href = $link->getAttribute('href');
                } else if ($link->hasAttribute('src')) {
                    $href = $link->getAttribute('src');
                } else {
                    continue;
                }
                //Trim any trailing slash if it exists to ensure the URL is unique
                $href = rtrim($href, "/");
                if ($this->isInternalLink($href)) {
                    if (strpos($href, '/') === 0) {
                        $href = parse_url($currentUrl, PHP_URL_SCHEME) . '://' . parse_url($currentUrl, PHP_URL_HOST) . $href;
                    }
                    $this->internalLinks[] = $href;
                    $hrefHost = parse_url($href, PHP_URL_HOST);
                    if (!in_array($href, $this->visitedPages) && !in_array($href, $queue) && ($this->crawlSubdomains || $hrefHost === $entryPointHost)) {
                        $queue[] = $href;
                    }
                } else {
                    $this->externalLinks[] = $href;
                }
            }

            //Extract word count
            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                $text = preg_replace('/\s+/', ' ', $body->textContent);
                $this->wordCounts[] = count(explode(' ', trim($text)));
            }

            //Extract title length
            $title = $dom->getElementsByTagName('title')->item(0);
            if ($title) {
                $this->titleLengths[] = strlen($title->textContent);
            }

            //Extract status code
            $headers = get_headers($currentUrl, 1);
            $statusLine = $headers[0];
            preg_match('/HTTP\/\d\.\d (\d{3})/', $statusLine, $matches);
            if (isset($matches[1])) {
                $this->pageStatusCodes[$currentUrl] = (int)$matches[1];
            } else {
                $this->pageStatusCodes[$currentUrl] = 0;
            }
        }
    }

    /**
     * Checks if a URL is an internal link or an external link
     *
     * @param string $url The URL to check
     * @return bool Whether the URL is an internal link or not
     */
    private function isInternalLink(string $url): bool
    {
        //Check if the URL is absolute or relative
        if (Str::startsWith($url, ['http://', 'https://'])) {
            $urlComponents = parse_url($url);
        } else {
            $urlComponents = parse_url($this->entryPoint);
            $urlComponents['path'] = $url;
        }

        if (!isset($urlComponents['path'])) {
            return false;
        }

        $urlHost = $urlComponents['host'] ?? '';
        $urlPath = rtrim($urlComponents['path'], '/');
        $entryPointHost = parse_url($this->entryPoint, PHP_URL_HOST);
        $entryPointPath = parse_url($this->entryPoint, PHP_URL_PATH);
        $hrefHost = parse_url($url, PHP_URL_HOST);

        return (
            $urlHost === '' || $urlHost === $entryPointHost ||
            ($this->countSubdomainsAsInternal && Str::endsWith($hrefHost, '.' . $entryPointHost))
        ) && (
            $urlPath === $entryPointPath ||
            Str::startsWith($urlPath, $entryPointPath . '/') ||
            $urlPath === ''
        );
    }

    /**
     * Displays the results of the web crawl
     *
     * @return \Illuminate\Contracts\Foundation\Application|ResponseFactory|Factory|Application|Response|View
     */
    private function displayResults(): Factory|Application|Response|View|\Illuminate\Contracts\Foundation\Application|ResponseFactory
    {
        if (!$this->dateTimeString) {
            $this->dateTimeString = date("Y-m-d_h-i-s");
        }
        $this->crawl($this->entryPoint, $this->maxPages);

        $uniqueImages = count(array_unique($this->images));
        $uniqueInternalLinks = count(array_unique($this->internalLinks));
        $uniqueExternalLinks = count(array_unique($this->externalLinks));
        $averagePageLoad = array_sum($this->pageLoadTimes) / count($this->pageLoadTimes);
        $averageWordCount = array_sum($this->wordCounts) / count($this->wordCounts);
        $averageTitleLength = array_sum($this->titleLengths) / count($this->titleLengths);


        $csvData = $this->generateCsv();
        $csvFileName = 'laravel_web_crawler_report_' . $this->dateTimeString . '.csv';
        $this->saveCsvToStorage($csvFileName, $csvData);
        if ($this->downloadCsvReport) {
            return response($csvData, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment;  filename="' . $csvFileName . '"',
            ]);
        }

        return view('results', [
            'visitedPages' => $this->visitedPages,
            'uniqueImages' => $uniqueImages,
            'pagesCrawled' => count($this->visitedPages),
            'uniqueInternalLinks' => $uniqueInternalLinks,
            'uniqueExternalLinks' => $uniqueExternalLinks,
            'averagePageLoad' => $averagePageLoad,
            'averageWordCount' => $averageWordCount,
            'averageTitleLength' => $averageTitleLength,
            'pageStatusCodes' => $this->pageStatusCodes,
            'countSubdomainsAsInternalLinks' => $this->countSubdomainsAsInternal,
            'crawlSubdomains' => $this->crawlSubdomains
        ]);
    }

    /**
     * Generates a CSV string containing metrics and visited pages versus page status codes
     *
     * @return string
     */
    private function generateCsv(): string
    {
        $csvData = '';

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, ['Laravel Web Crawler Report - ' . $this->dateTimeString]);
        //Add metrics to CSV
        fputcsv($handle, ['Metrics']);
        fputcsv($handle, ['Number of pages crawled', count($this->visitedPages)]);
        fputcsv($handle, ['Number of unique images', count(array_unique($this->images))]);
        fputcsv($handle, ['Number of unique internal links', count(array_unique($this->internalLinks))]);
        fputcsv($handle, ['Number of unique external links', count(array_unique($this->externalLinks))]);
        fputcsv($handle, ['Average page load in seconds', array_sum($this->pageLoadTimes) / count($this->pageLoadTimes)]);
        fputcsv($handle, ['Average word count', array_sum($this->wordCounts) / count($this->wordCounts)]);
        fputcsv($handle, ['Average title length', array_sum($this->titleLengths) / count($this->titleLengths)]);
        fputcsv($handle, ['Count subdomains as internal links', $this->countSubdomainsAsInternal ? 'Yes' : 'No']);
        fputcsv($handle, ['Crawl subdomains of the URL to crawl', $this->crawlSubdomains ? 'Yes' : 'No']);

        //Add an empty row between metrics and visited pages
        fputcsv($handle, []);

        //Add header row for visited pages
        fputcsv($handle, ['Visited Pages']);
        fputcsv($handle, [
            'URL',
            'HTTP Status Code',
        ]);

        //Add data rows for visited pages
        foreach ($this->visitedPages as $page) {
            fputcsv($handle, [
                $page,
                $this->pageStatusCodes[$page],
            ]);
        }

        //Read the CSV data into a string, using a reasonable buffer size of 8192
        rewind($handle);
        while (!feof($handle)) {
            $csvData .= fread($handle, 8192);
        }
        fclose($handle);

        return $csvData;
    }

    /**
     * Saves the CSV file to a local storage folder
     *
     * @param $csvFileName string The file name of the CSV file
     * @param $csvData string The CSV data of the file
     * @return void
     */
    private function saveCsvToStorage(string $csvFileName, string $csvData): void {
        $directory = storage_path('csv_reports');

        //Create directory if it doesn't exist
        if (!is_dir($directory)) {
            if (!mkdir($directory) && !is_dir($directory)) {
                throw new RuntimeException("Could not create CSV report directory!");
            }
        }

        //Write the CSV file with the provided CSV data
        $csvFilePath = $directory . '/' . $csvFileName;
        $file = fopen($csvFilePath, 'w');
        fwrite($file, $csvData);
        fclose($file);
    }
}
