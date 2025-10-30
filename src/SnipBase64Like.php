<?php

declare(strict_types=1);

namespace Smeghead\SnipBase64Like;

final class SnipBase64Like {
	private const DEFAULT_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=_-';

	private int $minLength;
	private int $previewLength;
	private string $alphabetPattern;

	public function __construct(int $minLength = 256, int $previewLength = 100)
	{
		$this->minLength = $minLength;
		$this->previewLength = max(0, $previewLength);
		$this->alphabetPattern = preg_quote(self::DEFAULT_ALPHABET, '/');
	}

	public function snip(mixed $value): mixed
	{
		if (is_string($value)) {
			return $this->snipString($value);
		}

		if (is_array($value)) {
			foreach ($value as $key => $item) {
				$value[$key] = $this->snip($item);
			}

			return $value;
		}

		if ($value instanceof \JsonSerializable) {
			return $this->snip($value->jsonSerialize());
		}

		if (is_object($value) && method_exists($value, '__toString')) {
			return $this->snipString((string) $value);
		}

		return $value;
	}

	private function snipString(string $value): string
	{
		$match = $this->analyseBase64Candidate($value);

		if ($match === null) {
			return $value;
		}

		return $this->buildPlaceholder($match['normalized'], $match['decoded']);
	}

	/**
	 * @return array{normalized: string, decoded: string}|null
	 */
	private function analyseBase64Candidate(string $value): ?array
	{
		$normalized = preg_replace('/\s+/', '', $value);

		if ($normalized === null) {
			return null;
		}

		if (strlen($normalized) < $this->minLength) {
			return null;
		}

		if (!preg_match('/^[' . $this->alphabetPattern . ']+$/', $normalized)) {
			return null;
		}

		$decoded = $this->decodeNormalizedBase64($normalized);

		if ($decoded === null) {
			return null;
		}

		return [
			'normalized' => $normalized,
			'decoded' => $decoded,
		];
	}

	private function decodeNormalizedBase64(string $normalized): ?string
	{
		$canonical = strtr($normalized, '-_', '+/');
		$padded = $canonical;
		$remainder = strlen($padded) % 4;

		if ($remainder !== 0) {
			$padded .= str_repeat('=', 4 - $remainder);
		}

		$decoded = base64_decode($padded, true);

		if ($decoded === false) {
			return null;
		}

		$reconstituted = base64_encode($decoded);

		if (rtrim($reconstituted, '=') !== rtrim($canonical, '=')) {
			return null;
		}

		return $decoded;
	}

	private function buildPlaceholder(string $normalized, string $decoded): string
	{
		if ($this->previewLength === 0) {
			return sprintf('[base64 payload ~%d bytes | preview:  ]', strlen($decoded));
		}

		$preview = substr($normalized, 0, $this->previewLength);
		$ellipsis = strlen($normalized) > $this->previewLength ? '...' : '';

		return sprintf('[base64 payload ~%d bytes | preview: %s%s ]', strlen($decoded), $preview, $ellipsis);
	}
}