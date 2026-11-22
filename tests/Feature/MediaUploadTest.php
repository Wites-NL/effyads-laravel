<?php

namespace EffyAds\Laravel\Tests\Feature;

use EffyAds\Laravel\EffyAds;
use EffyAds\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Storage;

class MediaUploadTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** A valid 2x3 RGB PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAIAAAADCAIAAAA2iEnWAAAAEElEQVR4nGP4z8AARAwoFABE0AX7pM/egAAAAABJRU5ErkJggg==';

    private function png(): string
    {
        return base64_decode(self::PNG);
    }

    private function upload(array $payload)
    {
        return $this->postJson('/effyads/v1/media/upload', $payload, $this->apiHeaders());
    }

    public function test_php_payload_disguised_as_png_is_rejected(): void
    {
        $this->upload([
            'filename' => 'x.php',
            'mime_type' => 'image/png',
            'base64_data' => base64_encode('<?php system($_GET["c"]); ?>'),
        ])->assertUnprocessable();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_php_appended_to_an_image_header_is_rejected(): void
    {
        // Starts with the PNG signature so finfo says image/png, but is not a decodable image.
        $this->upload([
            'filename' => 'x.png',
            'base64_data' => base64_encode("\x89PNG\r\n\x1a\n<?php echo 1; ?>"),
        ])->assertUnprocessable();

        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_valid_png_with_php_filename_is_stored_as_png(): void
    {
        $response = $this->upload([
            'filename' => 'x.php',
            'mime_type' => 'application/octet-stream',
            'base64_data' => base64_encode($this->png()),
        ])->assertCreated()
            ->assertJson(['mime_type' => 'image/png', 'width' => 2, 'height' => 3]);

        $files = Storage::disk('public')->allFiles();
        $this->assertCount(1, $files);
        $this->assertStringStartsWith('effyads/', $files[0]);
        $this->assertStringEndsWith('.png', $files[0]);
        $this->assertStringNotContainsString('php', $files[0]);
        $this->assertStringEndsWith('.png', $response->json('url'));
    }

    public function test_data_uri_prefix_is_accepted(): void
    {
        $this->upload([
            'filename' => 'a.png',
            'base64_data' => 'data:image/png;base64,'.base64_encode($this->png()),
        ])->assertCreated();
    }

    public function test_too_large_file_is_rejected(): void
    {
        config(['effyads.media_max_kb' => 1]);

        $this->upload([
            'filename' => 'big.png',
            'base64_data' => base64_encode(str_repeat('a', 2048)),
        ])->assertUnprocessable()
            ->assertJson(['message' => 'File is too large']);
    }

    public function test_invalid_base64_is_rejected(): void
    {
        $this->upload(['filename' => 'a.png', 'base64_data' => '***'])->assertUnprocessable();
    }

    public function test_multi_site_uploads_go_to_a_site_folder(): void
    {
        EffyAds::resolveSiteUsing(fn () => 'assen');
        EffyAds::resolveApiKeyUsing(fn () => self::API_KEY);

        $this->upload(['filename' => 'a.png', 'base64_data' => base64_encode($this->png())])->assertCreated();

        $this->assertStringStartsWith('effyads/assen/', Storage::disk('public')->allFiles()[0]);
    }
}
