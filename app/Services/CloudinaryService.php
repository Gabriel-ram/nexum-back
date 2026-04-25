<?php

namespace App\Services;

use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\UploadedFile;
use ReflectionProperty;

class CloudinaryService
{
    private Cloudinary $client;

    public function __construct()
    {
        // Bypass the ServiceProvider singleton whose isset($config['url']) check
        // returns false when the env value is null (e.g. stale config cache),
        // causing it to fall through to $config['cloud'] which doesn't exist.
        // Instantiating directly lets the SDK read CLOUDINARY_URL via getenv().
        $url = config('filesystems.disks.cloudinary.url');

        $this->client = $url ? new Cloudinary($url) : new Cloudinary();
    }

    /**
     * Upload a certification image to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadCertificationImage(UploadedFile $file): array
    {
        $result = $this->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'nexum/certifications',
        ]);

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
        ];
    }

    /**
     * Upload a project image to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function uploadProjectImage(UploadedFile $file): array
    {
        $result = $this->uploadApi()->upload($file->getRealPath(), [
            'folder' => 'nexum/projects/images',
        ]);

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
            'size'      => $result['bytes'],
        ];
    }

    /**
     * Upload a project PDF to Cloudinary.
     * Uses resource_type 'raw' so Cloudinary treats it as a generic file.
     *
     * @return array{url: string, public_id: string, size: int}
     */
    public function uploadProjectPdf(UploadedFile $file): array
    {
        $result = $this->uploadApi()->upload($file->getRealPath(), [
            'folder'        => 'nexum/projects/pdfs',
            'resource_type' => 'raw',
        ]);

        return [
            'url'       => $result['secure_url'],
            'public_id' => $result['public_id'],
            'size'      => $result['bytes'],
        ];
    }

    /**
     * Delete a project file from Cloudinary.
     * For PDFs the resource_type must be 'raw'; for images it is 'image' (default).
     */
    public function deleteProjectFile(?string $publicId, string $type): void
    {
        if (! $publicId) {
            return;
        }

        try {
            $resourceType = $type === 'pdf' ? 'raw' : 'image';
            $this->uploadApi()->destroy($publicId, ['resource_type' => $resourceType]);
        } catch (\Throwable) {
            // Silent — a failed cleanup must not fail the HTTP request
        }
    }

    /**
     * Delete a certification image from Cloudinary.
     * Silently ignores failures and null public IDs.
     */
    public function deleteCertificationImage(?string $publicId): void
    {
        if (! $publicId) {
            return;
        }

        try {
            $this->uploadApi()->destroy($publicId);
        } catch (\Throwable) {
            // Silent — a failed cleanup must not fail the HTTP request
        }
    }

    /**
     * Returns a ready-to-use UploadApi instance.
     *
     * In local development, SSL verification is disabled to work around the
     * missing Windows CA bundle (cURL error 60). The Cloudinary PHP SDK has no
     * config option for this, so we reach the internal Guzzle client via
     * reflection and replace it with one that has verify => false.
     *
     * In all other environments the original client — with full SSL — is used.
     */
    private function uploadApi(): UploadApi
    {
        $api = $this->client->uploadApi();

        if (! app()->environment('local')) {
            return $api;
        }

        // UploadApi::$apiClient is protected (type: UploadApiClient extends ApiClient).
        // ApiClient::$httpClient is public and ApiClient::getCloud()/getBaseUri() are public.
        $prop = new ReflectionProperty($api, 'apiClient');
        $prop->setAccessible(true);
        $apiClient = $prop->getValue($api);

        $cloud = $apiClient->getCloud();

        $apiClient->httpClient = new GuzzleClient([
            'base_uri'    => $apiClient->getBaseUri(),
            'verify'      => false,
            'auth'        => [$cloud->apiKey, $cloud->apiSecret],
            'http_errors' => false,
        ]);

        return $api;
    }
}
