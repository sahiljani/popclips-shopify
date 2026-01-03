<?php

namespace App\Services;

use App\Models\Shop;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected string $apiKey;

    protected string $apiSecret;

    protected string $scopes;

    protected string $appHost;

    public function __construct()
    {
        $this->apiKey = config('services.shopify.api_key');
        $this->apiSecret = config('services.shopify.api_secret');
        $this->scopes = config('services.shopify.scopes');
        $this->appHost = config('services.shopify.app_host');
    }

    public function getAuthUrl(string $shopDomain, string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'client_id' => $this->apiKey,
            'scope' => $this->scopes,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'grant_options[]' => 'per-user',
        ]);

        return "https://{$shopDomain}/admin/oauth/authorize?{$params}";
    }

    public function getAccessToken(string $shopDomain, string $code): ?string
    {
        try {
            $response = Http::withoutVerifying()
                ->post("https://{$shopDomain}/admin/oauth/access_token", [
                    'client_id' => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                    'code' => $code,
                ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            Log::error('Shopify access token error', [
                'shop' => $shopDomain,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Shopify access token exception', [
                'shop' => $shopDomain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculatedHmac = base64_encode(hash_hmac('sha256', $data, $this->apiSecret, true));

        return hash_equals($calculatedHmac, $hmacHeader);
    }

    public function verifyRequest(array $query): bool
    {
        if (! isset($query['hmac'])) {
            return false;
        }

        $hmac = $query['hmac'];
        unset($query['hmac']);

        ksort($query);
        $params = http_build_query($query);

        $calculatedHmac = hash_hmac('sha256', $params, $this->apiSecret);

        return hash_equals($calculatedHmac, $hmac);
    }

    public function getShopInfo(string $shopDomain, string $accessToken): ?array
    {
        try {
            $response = Http::withoutVerifying()
                ->withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                ])->get("https://{$shopDomain}/admin/api/2026-01/shop.json");

            if ($response->successful()) {
                return $response->json('shop');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Shopify shop info error', [
                'shop' => $shopDomain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getProducts(Shop $shop, array $params = []): array
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->get("https://{$shop->shopify_domain}/admin/api/2026-01/products.json", $params);

            if ($response->successful()) {
                return $response->json('products', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Shopify products error', [
                'shop' => $shop->shopify_domain,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function searchProducts(Shop $shop, string $query): array
    {
        return $this->getProducts($shop, [
            'title' => $query,
            'limit' => 20,
        ]);
    }

    public function getProduct(Shop $shop, string $productId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->get("https://{$shop->shopify_domain}/admin/api/2026-01/products/{$productId}.json");

            if ($response->successful()) {
                return $response->json('product');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Shopify product error', [
                'shop' => $shop->shopify_domain,
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function createRecurringCharge(Shop $shop, string $name, float $price, string $returnUrl): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->post("https://{$shop->shopify_domain}/admin/api/2026-01/recurring_application_charges.json", [
                'recurring_application_charge' => [
                    'name' => $name,
                    'price' => $price,
                    'return_url' => $returnUrl,
                    'test' => config('app.env') !== 'production',
                ],
            ]);

            if ($response->successful()) {
                return $response->json('recurring_application_charge');
            }

            Log::error('Shopify charge creation error', [
                'shop' => $shop->shopify_domain,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Shopify charge creation exception', [
                'shop' => $shop->shopify_domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getRecurringCharge(Shop $shop, string $chargeId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->get("https://{$shop->shopify_domain}/admin/api/2026-01/recurring_application_charges/{$chargeId}.json");

            if ($response->successful()) {
                return $response->json('recurring_application_charge');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Shopify get charge error', [
                'shop' => $shop->shopify_domain,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function cancelRecurringCharge(Shop $shop, string $chargeId): bool
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->delete("https://{$shop->shopify_domain}/admin/api/2026-01/recurring_application_charges/{$chargeId}.json");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Shopify cancel charge error', [
                'shop' => $shop->shopify_domain,
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function registerWebhook(Shop $shop, string $topic, string $address): bool
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
            ])->post("https://{$shop->shopify_domain}/admin/api/2026-01/webhooks.json", [
                'webhook' => [
                    'topic' => $topic,
                    'address' => $address,
                    'format' => 'json',
                ],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Shopify webhook registration error', [
                'shop' => $shop->shopify_domain,
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function graphql(Shop $shop, string $query, array $variables = []): ?array
    {
        try {
            $response = Http::withoutVerifying()->withHeaders([
                'X-Shopify-Access-Token' => $shop->access_token,
                'Content-Type' => 'application/json',
            ])->post("https://{$shop->shopify_domain}/admin/api/2026-01/graphql.json", [
                'query' => $query,
                'variables' => $variables,
            ]);

            if ($response->failed()) {
                Log::error('Shopify GraphQL request failed', [
                    'shop' => $shop->shopify_domain,
                    'status' => $response->status(),
                    'body' => $response->json(),
                ]);

                return null;
            }

            $payload = $response->json();

            if (isset($payload['errors']) && ! empty($payload['errors'])) {
                Log::error('Shopify GraphQL errors', [
                    'shop' => $shop->shopify_domain,
                    'errors' => $payload['errors'],
                ]);

                return null;
            }

            return $payload['data'] ?? null;
        } catch (\Exception $e) {
            Log::error('Shopify GraphQL exception', [
                'shop' => $shop->shopify_domain,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function createStagedUpload(Shop $shop, string $fileName, string $mimeType, int $fileSize): ?array
    {
        $mutation = <<<'GQL'
                        mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
                            stagedUploadsCreate(input: $input) {
                                stagedTargets {
                                    url
                                    resourceUrl
                                    parameters {
                                        name
                                        value
                                    }
                                }
                                userErrors {
                                    field
                                    message
                                }
                            }
                        }
                GQL;

        // Use VIDEO resource for video files as per Shopify documentation
        // https://shopify.dev/docs/api/admin-graphql/latest/mutations/stageduploadscreate
        $data = $this->graphql($shop, $mutation, [
            'input' => [[
                'resource' => 'VIDEO',
                'filename' => $fileName,
                'mimeType' => $mimeType,
                'fileSize' => (string) $fileSize,
                'httpMethod' => 'POST',
            ]],
        ]);

        if (! $data || ! empty($data['stagedUploadsCreate']['userErrors'])) {
            Log::error('Shopify staged upload error', [
                'shop' => $shop->shopify_domain,
                'errors' => $data['stagedUploadsCreate']['userErrors'] ?? [],
            ]);

            return null;
        }

        $target = $data['stagedUploadsCreate']['stagedTargets'][0] ?? null;

        if (! $target) {
            return null;
        }

        return [
            'url' => $target['url'],
            'resource_url' => $target['resourceUrl'],
            'parameters' => $target['parameters'],
        ];
    }

    public function completeFileUpload(Shop $shop, string $resourceUrl, string $fileName, ?string $altText = null): ?array
    {
        $mutation = <<<'GQL'
                        mutation fileCreate($files: [FileCreateInput!]!) {
                            fileCreate(files: $files) {
                                files {
                                    id
                                    createdAt
                                    fileStatus
                                    alt
                                    preview {
                                        image {
                                            url
                                        }
                                    }
                                    ... on Video {
                                        duration
                                        status
                                        sources {
                                            url
                                            format
                                            height
                                            width
                                            mimeType
                                        }
                                    }
                                }
                                userErrors {
                                    field
                                    message
                                }
                            }
                        }
                GQL;

        $data = $this->graphql($shop, $mutation, [
            'files' => [[
                'contentType' => 'VIDEO',
                'originalSource' => $resourceUrl,
                'alt' => $altText,
            ]],
        ]);

        // Log the full response to debug
        Log::info('Shopify fileCreate response', [
            'shop' => $shop->shopify_domain,
            'full_response' => $data,
        ]);

        if (! $data || ! empty($data['fileCreate']['userErrors'])) {
            Log::error('Shopify fileCreate error', [
                'shop' => $shop->shopify_domain,
                'errors' => $data['fileCreate']['userErrors'] ?? [],
                'full_response' => $data,
            ]);

            return null;
        }

        $file = $data['fileCreate']['files'][0] ?? null;

        if (! $file) {
            Log::error('Shopify fileCreate - no file in response', [
                'shop' => $shop->shopify_domain,
                'full_data' => $data,
            ]);
            return null;
        }

        $source = $file['sources'][0] ?? null;

        // Video files may not have sources immediately - Shopify processes them asynchronously
        // Return the file info even if sources aren't ready yet
        $result = [
            'id' => $file['id'],
            'url' => $source['url'] ?? null,
            'preview_image_url' => $file['preview']['image']['url'] ?? null,
            'duration' => $file['duration'] ?? null,
            'file_status' => $file['fileStatus'] ?? 'PROCESSING',
            'original_filename' => $fileName,
            'sources' => $file['sources'] ?? [],
            'status' => ($source && $source['url']) ? 'ready' : 'processing',
        ];

        Log::info('Shopify fileCreate success', [
            'shop' => $shop->shopify_domain,
            'result' => $result,
            'note' => empty($file['sources']) ? 'Video is processing, sources not available yet' : 'Video ready with sources',
        ]);

        return $result;
    }

    public function getFileById(Shop $shop, string $fileId): ?array
    {
        $query = <<<'GQL'
                        query getFile($id: ID!) {
                            file: node(id: $id) {
                                ... on Video {
                                    id
                                    fileStatus
                                    duration
                                    status
                                    preview {
                                        image {
                                            url
                                        }
                                    }
                                    sources {
                                        url
                                        format
                                        height
                                        width
                                        mimeType
                                    }
                                }
                                ... on GenericFile {
                                    id
                                    fileStatus
                                    preview {
                                        image {
                                            url
                                        }
                                    }
                                }
                            }
                        }
                GQL;

        $data = $this->graphql($shop, $query, ['id' => $fileId]);

        if (! $data || ! isset($data['file'])) {
            return null;
        }

        $file = $data['file'];
        $source = $file['sources'][0] ?? null;

        return [
            'id' => $file['id'],
            'url' => $source['url'] ?? null,
            'preview_image_url' => $file['preview']['image']['url'] ?? null,
            'duration' => $file['duration'] ?? null,
            'file_status' => $file['fileStatus'] ?? null,
            'sources' => $file['sources'] ?? [],
            'status' => ($source && $source['url']) ? 'ready' : 'processing',
        ];
    }

    public function listVideoFiles(Shop $shop, ?string $search = null, ?string $after = null, int $first = 20): ?array
    {
        $query = <<<'GQL'
                        query listVideoFiles($first: Int!, $after: String, $query: String) {
                            files(first: $first, after: $after, query: $query) {
                                edges {
                                    cursor
                                    node {
                                        id
                                        createdAt
                                        fileStatus
                                        alt
                                        preview {
                                            image {
                                                url
                                            }
                                        }
                                        ... on Video {
                                            duration
                                            status
                                            sources {
                                                url
                                                format
                                                height
                                                width
                                                mimeType
                                            }
                                        }
                                    }
                                }
                                pageInfo {
                                    hasNextPage
                                    endCursor
                                }
                            }
                        }
                GQL;

        $queryString = 'media_type:video';
        if ($search) {
            $queryString .= ' '.$search;
        }

        $data = $this->graphql($shop, $query, [
            'first' => $first,
            'after' => $after,
            'query' => $queryString,
        ]);

        if (! $data) {
            return null;
        }

        return $data['files'];
    }
}
