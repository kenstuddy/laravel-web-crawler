@extends('layouts.header')
@section('title', 'Laravel Web Crawler Results')
<body>
    @include('layouts.navbar')
    <div class="container mt-5">
        <h1>Laravel Web Crawler Crawl Results</h1>

        <h2>Metrics</h2>
        <ul>
            <li>Number of pages crawled: {{ $pagesCrawled }}</li>
            <li>Number of unique images: {{ $uniqueImages }}</li>
            <li>Number of unique internal links: {{ $uniqueInternalLinks }}</li>
            <li>Number of unique external links: {{ $uniqueExternalLinks }}</li>
            <li>Average page load in seconds: {{ $averagePageLoad }}</li>
            <li>Average word count: {{ $averageWordCount }}</li>
            <li>Average title length: {{ $averageTitleLength }}</li>
            <li>Count subdomains as internal links: {{ $countSubdomainsAsInternalLinks ? "Yes" : "No" }}</li>
            <li>Crawl subdomains of the URL to crawl: {{ $crawlSubdomains ? "Yes" : "No" }}</li>
        </ul>

        <h2>Visited Pages</h2>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>URL</th>
                        <th>HTTP Status Code</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($visitedPages as $index => $page)
                        <tr>
                            <td>{{ $page }}</td>
                            <td>{{ $pageStatusCodes[$page] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @include('layouts.scripts')
</body>
@extends('layouts.footer')
