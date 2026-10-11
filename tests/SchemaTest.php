<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Errors\InvalidPaymentStructure;
use Savia\NubeFactIntegration\Schema;

final class SchemaTest extends TestCase
{
    #[Test]
    public function it_returns_the_structure_when_every_required_key_is_present(): void
    {
        self::assertSame(['serie' => 'B001', 'cliente_email' => null], self::schema(['serie' => 'B001', 'cliente_email' => null])->value());
    }

    #[Test]
    public function it_rejects_a_structure_without_a_required_key(): void
    {
        $this->expectException(InvalidPaymentStructure::class);

        self::schema(['serie' => 'B001'])->value();
    }

    private static function schema(array $structure): Schema
    {
        return new class ($structure) extends Schema {
            public function __construct(private readonly array $structure) {}

            protected static function required(): array
            {
                return ['serie', 'cliente_email'];
            }

            protected function structure(): array
            {
                return $this->structure;
            }
        };
    }
}
