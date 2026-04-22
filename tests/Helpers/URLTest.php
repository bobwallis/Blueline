<?php

namespace Blueline\Tests\Helpers;

use Blueline\Helpers\URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{
    public function testCanonicalAppliesManualCorrections(): void
    {
        $this->assertSame(
            'Sutton-cum-Lound_Surprise_Minor',
            URL::canonical('Sutton_cum_Lound_S_Minor')
        );
    }

    public function testCanonicalExpandsClassificationInitials(): void
    {
        $this->assertSame(
            'Cambridge_Surprise_Minor',
            URL::canonical('Cambridge_S_Minor')
        );
    }

    public function testCanonicalNormalisesNoNumberAndCamelCaseInputs(): void
    {
        $this->assertSame(
            'Cambridge_Surprise_Minor',
            URL::canonical('CambridgeSurpriseMinor')
        );

        $this->assertSame(
            'FerretReplacementNo_1BobMinor',
            URL::canonical('FerretReplacementNo1BobMinor')
        );
    }
}
