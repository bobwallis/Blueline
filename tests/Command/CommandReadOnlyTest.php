<?php

namespace Blueline\Tests\Command;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class CommandReadOnlyTest extends CommandTestCase
{
    public function testImportMethodsCommandIsRegisteredWithExpectedDescription(): void
    {
        $command = $this->getCommand('blueline:importMethods');

        $this->assertSame('blueline:importMethods', $command->getName());
        $this->assertStringContainsString('Imports method data with the most recent data which has been fetched', $command->getDescription());
    }

    public function testExportCollectionsCommandOutputsExpectedPhpShape(): void
    {
        [$exitCode, $output] = $this->executeCommandStreamed('blueline:exportCollections');

        $this->assertSame(0, $exitCode);
        $this->assertStringStartsWith("<?php\n// Collections data exported ", $output);
        $this->assertStringContainsString("\n\$collections = ", $output);
        $this->assertStringContainsString("array (", $output);
        $this->assertNoConsoleErrors($output);
    }

    public function testExportMethodExtrasCommandOutputsExpectedPhpShape(): void
    {
        [$exitCode, $output] = $this->executeCommandStreamed('blueline:exportMethodExtras');

        $this->assertSame(0, $exitCode);
        $this->assertStringStartsWith("<?php\n// Extra method data exported ", $output);
        $this->assertStringContainsString("\n\$method_extras = ", $output);
        $this->assertStringContainsString("array (", $output);
        $this->assertNoConsoleErrors($output);
    }

    public function testCheckMethodClassificationsRunsAndDoesNotHitDbFailures(): void
    {
        $command = $this->getCommand('blueline:checkMethodClassifications');

        $this->assertSame('blueline:checkMethodClassifications', $command->getName());
        $this->assertStringContainsString('Check for mismatches between the method library', $command->getDescription());
    }
}
