<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Smeghead\SnipBase64Like\SnipBase64Like;

final class SnipBase64LikeTest extends TestCase
{
    public function testSnipsLongBase64String(): void
    {
        $payload = str_repeat('0123456789', 32); // 320 bytes
        $base64 = base64_encode($payload);

        $sut = new SnipBase64Like(minLength: 50, previewLength: 12);

        $masked = $sut->snip($base64);

        $expectedPreview = substr($base64, 0, 12);
        $expected = sprintf('[base64 payload ~%d bytes | preview: %s... ]', strlen($payload), $expectedPreview);

        $this->assertSame($expected, $masked);
    }

    public function testKeepsShortBase64Intact(): void
    {
        $payload = 'short payload';
        $base64 = base64_encode($payload);

        $sut = new SnipBase64Like(minLength: strlen($base64) + 1, previewLength: 12);

        $this->assertSame($base64, $sut->snip($base64));
    }

    public function testKeepsNonBase64StringIntact(): void
    {
    $sut = new SnipBase64Like(minLength: 10);

    $input = str_repeat('not-base64!#', 50);

        $this->assertSame($input, $sut->snip($input));
    }

    public function testSnipsBase64StringsInsideArrays(): void
    {
        $binary = str_repeat('foo', 200);
        $base64 = base64_encode($binary);

        $sut = new SnipBase64Like(minLength: 50, previewLength: 8);

        $sanitized = $sut->snip([
            'id' => 1,
            'payload' => $base64,
        ]);

        $expectedPreview = substr($base64, 0, 8);
        $expected = sprintf('[base64 payload ~%d bytes | preview: %s... ]', strlen($binary), $expectedPreview);

        $this->assertSame([
            'id' => 1,
            'payload' => $expected,
        ], $sanitized);
    }

    public function testSnipsJsonSerializablePayload(): void
    {
        $binary = random_bytes(180);
        $base64 = base64_encode($binary);
        $expectedPreview = substr($base64, 0, 10);

        $sut = new SnipBase64Like(minLength: 50, previewLength: 10);

        $payload = new class($base64) implements \JsonSerializable {
            public function __construct(private string $content) {}

            public function jsonSerialize(): array
            {
                return [
                    'content' => $this->content,
                ];
            }
        };

        $sanitized = $sut->snip($payload);

        $expected = sprintf('[base64 payload ~%d bytes | preview: %s... ]', strlen($binary), $expectedPreview);

        $this->assertSame([
            'content' => $expected,
        ], $sanitized);
    }

    public function testStringableObjectGetsSnipped(): void
    {
        $binary = random_bytes(90);
        $base64 = base64_encode($binary);

        $sut = new SnipBase64Like(minLength: 40, previewLength: 6);

        $stringable = new class($base64) {
            public function __construct(private string $content) {}

            public function __toString(): string
            {
                return $this->content;
            }
        };

        $expectedPreview = substr($base64, 0, 6);
        $expected = sprintf('[base64 payload ~%d bytes | preview: %s... ]', strlen($binary), $expectedPreview);

        $this->assertSame($expected, $sut->snip($stringable));
    }
}
