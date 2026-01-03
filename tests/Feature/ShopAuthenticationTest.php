<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Shop $activeShop;
    protected Shop $inactiveShop;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test shops
        $this->activeShop = Shop::create([
            'shopify_domain' => 'active-shop.myshopify.com',
            'name' => 'Active Shop',
            'access_token' => 'test_token_123',
            'is_active' => true,
        ]);

        $this->inactiveShop = Shop::create([
            'shopify_domain' => 'inactive-shop.myshopify.com',
            'name' => 'Inactive Shop',
            'access_token' => 'test_token_456',
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_rejects_requests_without_shop_parameter()
    {
        $response = $this->getJson('/api/v1/clips');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Shop domain required',
            ])
            ->assertJsonStructure([
                'error',
                'message',
                'hint',
            ]);
    }

    /** @test */
    public function it_provides_helpful_hint_when_shop_parameter_is_missing()
    {
        $response = $this->getJson('/api/v1/clips');

        $response->assertStatus(401)
            ->assertJsonFragment([
                'hint' => 'Try adding ?shop=active-shop.myshopify.com to your URL',
            ]);
    }

    /** @test */
    public function it_accepts_shop_parameter_from_query_string()
    {
        $response = $this->getJson('/api/v1/clips?shop=active-shop.myshopify.com');

        // Should pass authentication (may fail with other errors but not auth)
        $response->assertStatus(200);
    }

    /** @test */
    public function it_accepts_shop_parameter_from_header()
    {
        $response = $this->getJson('/api/v1/clips', [
            'X-Shop-Domain' => 'active-shop.myshopify.com',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function it_rejects_inactive_shops()
    {
        $response = $this->getJson('/api/v1/clips?shop=inactive-shop.myshopify.com');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Shop is inactive',
                'message' => 'This shop exists but is currently inactive',
                'shop_domain' => 'inactive-shop.myshopify.com',
            ]);
    }

    /** @test */
    public function it_rejects_non_existent_shops()
    {
        $response = $this->getJson('/api/v1/clips?shop=non-existent.myshopify.com');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Shop not found',
                'message' => 'No shop found with domain: non-existent.myshopify.com',
                'provided_domain' => 'non-existent.myshopify.com',
            ])
            ->assertJsonStructure([
                'error',
                'message',
                'provided_domain',
                'active_shops',
                'hint',
            ]);
    }

    /** @test */
    public function it_provides_list_of_active_shops_in_error_message()
    {
        $response = $this->getJson('/api/v1/clips?shop=wrong-shop.myshopify.com');

        $response->assertStatus(404)
            ->assertJsonFragment([
                'active_shops' => ['active-shop.myshopify.com'],
            ]);
    }

    /** @test */
    public function it_stores_shop_domain_in_session()
    {
        $this->getJson('/api/v1/clips?shop=active-shop.myshopify.com');

        $this->assertEquals('active-shop.myshopify.com', session('shop_domain'));
    }

    /** @test */
    public function it_retrieves_shop_domain_from_session_on_subsequent_requests()
    {
        // First request with shop parameter
        $this->getJson('/api/v1/clips?shop=active-shop.myshopify.com');

        // Second request without shop parameter (should use session)
        $response = $this->getJson('/api/v1/clips');

        $response->assertStatus(200);
    }

    /** @test */
    public function query_parameter_takes_precedence_over_session()
    {
        // Store a different shop in session
        session(['shop_domain' => 'inactive-shop.myshopify.com']);

        // Make request with different shop in query
        $response = $this->getJson('/api/v1/clips?shop=active-shop.myshopify.com');

        $response->assertStatus(200);
        $this->assertEquals('active-shop.myshopify.com', session('shop_domain'));
    }

    /** @test */
    public function header_takes_precedence_over_session()
    {
        // Store a different shop in session
        session(['shop_domain' => 'inactive-shop.myshopify.com']);

        // Make request with different shop in header
        $response = $this->getJson('/api/v1/clips', [
            'X-Shop-Domain' => 'active-shop.myshopify.com',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('active-shop.myshopify.com', session('shop_domain'));
    }

    /** @test */
    public function it_allows_creating_clips_with_valid_shop()
    {
        $response = $this->postJson('/api/v1/clips?shop=active-shop.myshopify.com', [
            'title' => 'Test Clip',
            'description' => 'Test Description',
            'shopify_file_id' => 'gid://shopify/MediaImage/123',
            'video_url' => 'https://example.com/video.mp4',
            'thumbnail_url' => 'https://example.com/thumb.jpg',
            'duration' => 30,
            'file_size' => 1024000,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'shop_id',
                'title',
                'description',
                'video_url',
            ]);

        $this->assertDatabaseHas('clips', [
            'shop_id' => $this->activeShop->id,
            'title' => 'Test Clip',
        ]);
    }
}
