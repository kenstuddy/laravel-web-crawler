<!DOCTYPE html>
<html>
<head>
    <title>Laravel Web Crawler</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container my-5">
        <h1 class="text-center">Laravel Web Crawler</h1>
        <div class="card mx-auto mt-5" style="max-width: 500px;">
            <div class="card-body">
                <p class="card-text">This is a web crawler that can crawl a website and display various statistics about the pages it crawls.</p>
                <form action="/crawl" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="entry_point" class="form-label">URL:</label>
                        <input type="url" class="form-control" id="entry_point" name="entry_point" placeholder="Enter the URL of the website you want to crawl" required>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="count_subdomains_as_internal" value="true" id="count_subdomains_as_internal" checked>
                        <label class="form-check-label" for="count_subdomains_as_internal">
                            Count subdomains as internal links
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary">Crawl Website</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
