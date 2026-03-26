<?php
namespace Blueline\Tests\Command;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class ZCalculateMethodSimilaritiesInteractiveTest extends CommandTestCase
{
    public function testCalculateMethodSimilaritiesCanAbortViaConfirmationPrompt(): void
    {
        $titlesResult = pg_query($this->getDb(), 'SELECT title FROM methods ORDER BY title ASC LIMIT 30');
        $this->assertNotFalse($titlesResult, 'Failed to select sample method titles: '.pg_last_error($this->getDb()));

        $titles = array_column(pg_fetch_all($titlesResult) ?: [], 'title');
        $this->assertCount(30, $titles, 'Expected at least 30 methods in the test database for interactive-branch test');

        foreach ($titles as $title) {
            $this->dbExec('DELETE FROM methods_similar WHERE method1_title = $1 AND method2_title = $1', [$title]);
        }

        $missingBefore = $this->dbCount(
            'SELECT COUNT(*)
               FROM methods m
               LEFT JOIN methods_similar ms ON (m.title = ms.method1_title AND m.title = ms.method2_title)
              WHERE ms.method1_title IS NULL'
        );
        $this->assertGreaterThanOrEqual(25, $missingBefore);

        [$exitCode, $output] = $this->executeCommand('blueline:calculateMethodSimilarities', [], ['n']);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Continue calculating similarities?', $output);
        $this->assertNoConsoleErrors($output);

        $missingAfter = $this->dbCount(
            'SELECT COUNT(*)
               FROM methods m
               LEFT JOIN methods_similar ms ON (m.title = ms.method1_title AND m.title = ms.method2_title)
              WHERE ms.method1_title IS NULL'
        );
        $this->assertSame($missingBefore, $missingAfter, 'Expected no similarity recalculation after answering no to confirmation prompt');

        foreach ($titles as $title) {
            $exists = $this->dbCount('SELECT COUNT(*) FROM methods_similar WHERE method1_title = $1 AND method2_title = $1', [$title]);
            if ($exists === 0) {
                $this->dbExec(
                    'INSERT INTO methods_similar (method1_title, method2_title, similarity) VALUES ($1, $1, 0)',
                    [$title]
                );
            }
        }
    }

}
