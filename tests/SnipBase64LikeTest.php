<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Smeghead\SnipBase64Like\SnipBase64Like;

final class SnipBase64LikeTest extends TestCase
{
    public function testInitialize(): void
    {
        $sut = new SnipBase64Like(null);

        $this->assertNotNull($sut, 'initialize');
    }
}
