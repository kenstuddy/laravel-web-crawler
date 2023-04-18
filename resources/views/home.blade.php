@extends('layouts.app')
@section('title', 'Laravel Web Crawler')
@section('content')
    @include('layouts.navbar')
    <div class="container my-5">
        <h1 class="text-center">Laravel Web Crawler</h1>
        <div class="card mx-auto mt-5" style="max-width: 500px;">
            <div class="card-body">
                <p class="card-text">This is a web crawler that can crawl a website and display various statistics about the pages it crawls.</p>
                <form action="/crawl" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="entry_point" class="form-label">URL to Crawl:</label>
                        <input type="url" class="form-control" id="entry_point" name="entry_point" placeholder="Enter the URL of the website you want to crawl" required>
                    </div>
                    <div class="mb-3">
                        <label for="entry_point" class="form-label">Maximum Pages to Crawl:</label>
                        <input type="number" class="form-control" id="max_pages" name="max_pages" placeholder="Enter the number of pages you want to crawl" min="1" max="{{ $maxPages }}" value="{{ min(6, $maxPages) }}" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="count_subdomains_as_internal" value="true" id="count_subdomains_as_internal" checked>
                        <label class="form-check-label" for="count_subdomains_as_internal">
                            Count Subdomains as Internal Links
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="crawl_subdomains" value="true" id="crawl_subdomains" checked>
                        <label class="form-check-label" for="crawl_subdomains">
                            Crawl Subdomains of the URL to Crawl
                        </label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="download_csv_report" value="true" id="download_csv_report">
                        <label class="form-check-label" for="download_csv_report">
                            Download Web Crawler CSV Report
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Crawl Website</button>
                </form>
            </div>
        </div>
    </div>
    @include('layouts.scripts')
@endsection

