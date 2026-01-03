<?php

namespace App\Services;

use App\Models\Clip;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected string $cloudName;

    protected string $apiKey;

    protected string $apiSecret;

    public function __construct()
    {
        $this->cloudName = config('services.cloudinary.cloud_name');
        $this->apiKey = config('services.cloudinary.api_key');
        $this->apiSecret = config('services.cloudinary.api_secret');
    }

    public function uploadVideoAsync(Clip $clip, UploadedFile $file): void
    {
        dispatch(function () use ($clip, $file) {
            $this->uploadVideo($clip, $file);
        })->afterResponse();
    }

    public function uploadVideo(Clip $clip, UploadedFile $file): ?array
    {
        try {
            $timestamp = time();
            $folder = "popclips/{$clip->shop_id}";
            $publicId = "clip_{$clip->id}";

            $params = [
                'folder' => $folder,
                'public_id' => $publicId,
                'resource_type' => 'video',
                'timestamp' => $timestamp,
                'eager' => 'sp_auto',
                'eager_async' => true,
                'eager_notification_url' => route('cloudinary.webhook'),
            ];

            ksort($params);
            $toSign = collect($params)
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode('&');
            $signature = sha1($toSign.$this->apiSecret);

            $response = Http::attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )->post("https://api.cloudinary.com/v1_1/{$this->cloudName}/video/upload", [
                ...$params,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $clip->update([
                    'video_url' => $data['secure_url'],
                    'thumbnail_url' => str_replace('.mp4', '.jpg', $data['secure_url']),
                    'cloudinary_public_id' => $data['public_id'],
                    'duration' => $data['duration'] ?? null,
                    'file_size' => $data['bytes'] ?? null,
                    'status' => 'ready',
                ]);

                return $data;
            }

            Log::error('Cloudinary upload failed', [
                'clip_id' => $clip->id,
                'response' => $response->json(),
            ]);

            $clip->update(['status' => 'failed']);

            return null;
        } catch (\Exception $e) {
            Log::error('Cloudinary upload exception', [
                'clip_id' => $clip->id,
                'error' => $e->getMessage(),
            ]);

            $clip->update(['status' => 'failed']);

            return null;
        }
    }

    public function deleteVideo(string $publicId): bool
    {
        try {
            $timestamp = time();

            $params = [
                'public_id' => $publicId,
                'timestamp' => $timestamp,
            ];

            ksort($params);
            $toSign = collect($params)
                ->map(fn ($value, $key) => "{$key}={$value}")
                ->implode('&');
            $signature = sha1($toSign.$this->apiSecret);

            $response = Http::post("https://api.cloudinary.com/v1_1/{$this->cloudName}/video/destroy", [
                ...$params,
                'api_key' => $this->apiKey,
                'signature' => $signature,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Cloudinary delete exception', [
                'public_id' => $publicId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getVideoUrl(string $publicId, array $transformations = []): string
    {
        $transforms = '';

        if (! empty($transformations)) {
            $transforms = '/'.collect($transformations)
                ->map(fn ($value, $key) => "{$key}_{$value}")
                ->implode(',');
        }

        return "https://res.cloudinary.com/{$this->cloudName}/video/upload{$transforms}/{$publicId}";
    }

    public function getThumbnailUrl(string $publicId, int $time = 0): string
    {
        return "https://res.cloudinary.com/{$this->cloudName}/video/upload/so_{$time}/{$publicId}.jpg";
    }

    public function generateUploadSignature(array $params): string
    {
        $params['timestamp'] = $params['timestamp'] ?? time();

        ksort($params);
        $toSign = collect($params)
            ->map(fn ($value, $key) => "{$key}={$value}")
            ->implode('&');

        return sha1($toSign.$this->apiSecret);
    }

    public function getUploadParams(int $shopId): array
    {
        $timestamp = time();
        $folder = "popclips/{$shopId}";

        $params = [
            'folder' => $folder,
            'resource_type' => 'video',
            'timestamp' => $timestamp,
        ];

        return [
            'cloudName' => $this->cloudName,
            'apiKey' => $this->apiKey,
            'timestamp' => $timestamp,
            'folder' => $folder,
            'signature' => $this->generateUploadSignature($params),
        ];
    }
}
