<?php
namespace Blueline\Tests\Command;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class CommandWorkflowTest extends CommandTestCase
{
    public function testImportMethodsRefreshesCoreDataInvariantsWhenSlowTestsEnabled(): void
    {
        if (getenv('BLUELINE_RUN_SLOW_COMMAND_TESTS') !== '1') {
            $this->markTestSkipped('Set BLUELINE_RUN_SLOW_COMMAND_TESTS=1 to run importMethods integration coverage.');
        }

        [$importMethodsExitCode, $importMethodsOutput] = $this->executeCommand('blueline:importMethods', [], ['y']);

        $this->assertSame(0, $importMethodsExitCode);
        $this->assertStringContainsString('Updating method data', $importMethodsOutput);
        $this->assertStringContainsString('Finished updating method data', $importMethodsOutput);
        $this->assertNoConsoleErrors($importMethodsOutput);
        $this->assertGreaterThan(0, $this->dbCount('SELECT COUNT(*) FROM methods'));
        $this->assertSame(2, $this->dbCount("SELECT COUNT(*) FROM collections WHERE id IN ('pmm', 'tdmm')"));
    }

    public function testImportExtrasAndCollectionsMaintainCoreInvariants(): void
    {
        $this->assertGreaterThan(0, $this->dbCount('SELECT COUNT(*) FROM methods'));

        [$extrasExitCode, $extrasOutput] = $this->executeCommand('blueline:importMethodExtras');

        $this->assertSame(0, $extrasExitCode);
        $this->assertStringContainsString('Updating extra method data', $extrasOutput);
        $this->assertStringContainsString('Finished updating extra method data', $extrasOutput);
        $this->assertGreaterThan(0, $this->dbCount("SELECT COUNT(*) FROM performances WHERE type = 'renamedMethod'"));
        $this->assertGreaterThan(0, $this->dbCount('SELECT COUNT(*) FROM methods WHERE calls IS NOT NULL OR ruleoffs IS NOT NULL'));

        [$collectionsExitCode, $collectionsOutput] = $this->executeCommand('blueline:importCollections');

        $this->assertSame(0, $collectionsExitCode);
        $this->assertStringContainsString('Updating collection data', $collectionsOutput);
        $this->assertStringContainsString('Finished updating method collection data', $collectionsOutput);
        $this->assertNoConsoleErrors($collectionsOutput);
        $this->assertGreaterThan(0, $this->dbCount("SELECT COUNT(*) FROM collections WHERE id NOT IN ('pmm', 'tdmm')"));
        $this->assertGreaterThan(0, $this->dbCount("SELECT COUNT(*) FROM methods_collections WHERE collection_id NOT IN ('pmm', 'tdmm')"));
        $this->assertSame(2, $this->dbCount("SELECT COUNT(*) FROM collections WHERE id IN ('pmm', 'tdmm')"));

        $this->assertGreaterThan(0, $this->dbCount('SELECT COUNT(*) FROM methods_similar'));
    }
}
