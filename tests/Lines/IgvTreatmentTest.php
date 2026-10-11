<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\InvalidLine;
use Savia\NubeFactIntegration\Tests\TestCase;

final class IgvTreatmentTest extends TestCase
{
    public static function treatments(): iterable
    {
        yield 'taxed included' => [IGVTreatment::taxedIncluded(), 'taxed_included', IGVType::GravadoOnerous];
        yield 'exonerated' => [IGVTreatment::exonerated(), 'exonerated', IGVType::ExoneratedOnerous];
        yield 'unaffected' => [IGVTreatment::unaffected(), 'unaffected', IGVType::ExemptOnerous];
    }

    #[Test]
    #[DataProvider('treatments')]
    public function it_round_trips_through_its_persistence_key(IGVTreatment $treatment, string $key, IGVType $type): void
    {
        self::assertSame($key, $treatment->key());
        self::assertSame($key, IGVTreatment::fromKey($key)->key());
        self::assertSame($type, $treatment->toNubefact());
    }

    #[Test]
    public function it_rejects_an_unknown_persistence_key(): void
    {
        $this->expectException(InvalidLine::class);
        IGVTreatment::fromKey('taxed_excluded');
    }
}
