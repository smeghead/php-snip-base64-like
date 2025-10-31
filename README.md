# php-snip-base64-like

[![Latest Stable Version](https://img.shields.io/packagist/v/smeghead/php-snip-base64-like.svg?style=flat-square)](https://packagist.org/packages/smeghead/php-snip-base64-like)
[![Total Downloads](https://img.shields.io/packagist/dt/smeghead/php-snip-base64-like.svg?style=flat-square)](https://packagist.org/packages/smeghead/php-snip-base64-like)
[![PHP Version](https://img.shields.io/packagist/php-v/smeghead/php-snip-base64-like.svg?style=flat-square)](https://packagist.org/packages/smeghead/php-snip-base64-like)

A PHP helper that trims base64-like blobs out of log messages so your log files stay readable and small while still recording the contextual data you need.

## Why Snip Base64?

In one of our production projects we forward detailed API interaction logs to operations. Some endpoints embed files as base64 strings inside JSON payloads; keeping those payloads verbatim caused the log volume to explode. Completely discarding the payload was not an option—we still needed an audit trail. This library strikes the balance by detecting base64-like strings, recording their presence, and replacing the verbose payload with a concise placeholder.

## Features

- Detects base64-like text using configurable length thresholds and alphabet heuristics.
- Replaces oversized segments with a compact summary that records the original byte size and a short preview.
- Supports both raw strings and structured payloads (arrays, `JsonSerializable`, objects implementing `__toString()`).
- Keeps the surrounding JSON or log structure intact so downstream tooling still works.

## Installation

```bash
composer require smeghead/php-snip-base64-like
```

The package provides an autoloadable `Smeghead\SnipBase64Like\SnipBase64Like` class.

## Quick Start

```php
use Smeghead\SnipBase64Like\SnipBase64Like;

$snipper = new SnipBase64Like(
    minLength: 256,            // base64 strings shorter than this are kept
    previewLength: 100         // keep 100 leading characters from the base64 blob
);

$logPayload = [
	'endpoint' => '/v1/upload',
	'requestId' => 'req-12345',
	'payload' => [
		'metadata' => ['filename' => 'report.pdf'],
		'file' => $veryLargeBase64String,
	],
];

$sanitized = $snipper->snip($logPayload);

echo json_encode($sanitized, JSON_PRETTY_PRINT);
/*
{
	"endpoint": "/v1/upload",
	"requestId": "req-12345",
	"payload": {
		"metadata": {
			"filename": "report.pdf"
		},
		"file": "[base64 payload ~42816 bytes | preview: JVBERi0xLjQKJc... ]"
	}
}
*/
```

Pass strings, arrays, or objects—`snip()` will walk the structure and redact any values that look like oversized base64 blocks.

## Configuration Highlights

- `minLength`: Minimum character count that triggers redaction. Tune this to your payload sizes.
- `previewLength`: Number of leading characters preserved in the placeholder (defaults to 100 so you retain enough context to recognise the payload while keeping logs lean).
- Placeholder format: `[base64 payload ~<bytes> bytes | preview: <prefix>... ]` is emitted automatically with the estimated original size and truncated preview.

## Testing

Run the PHPUnit suite after making changes:

```bash
composer test
```

PHPUnit uses the project’s `phpunit.xml` bootstrap to load Composer autoloading and the `tests/` directory.

## Status & Roadmap

- [ ] Implement recursive snipping logic with streaming-safe heuristics.
- [ ] Add JSON Path helper to target only selected fields.
- [ ] Provide PSR-3 processor middleware for Monolog.
- [ ] Document strategies for masking secrets alongside base64 data.

Contributions are welcome—open an issue or submit a pull request to discuss new ideas.

## License

MIT
