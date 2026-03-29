<?php
namespace Blueline\Tests\Command;

use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[RunTestsInSeparateProcesses]
class ZCalculateMethodSimilaritiesInteractiveTest extends CommandTestCase
{
    public function testCalculateMethodSimilaritiesCanTargetSpecificMethods(): void
    {
        $targetMethods = array(
            'Bristol Surprise Maximus',
            'Cambridge Surprise Minor',
            'Plain Bob Doubles',
            'Little Bob Royal',
            'Stedman Cinques',
        );

        $targetPlaceholders = implode(', ', array_fill(0, count($targetMethods), '?'));
        $availableTargetMethods = array_column(
            $this->connection->executeQuery(
                'SELECT title FROM methods WHERE title IN ('.$targetPlaceholders.') ORDER BY title ASC',
                $targetMethods
            )->fetchAllAssociative(),
            'title'
        );
        if (count($availableTargetMethods) !== count($targetMethods)) {
            $this->markTestSkipped('Expected named methods are not all available in test data.');
        }

        $this->dbExec(
            'DELETE FROM methods_similar WHERE method1_title IN ('.$targetPlaceholders.') OR method2_title IN ('.$targetPlaceholders.')',
            array_merge($targetMethods, $targetMethods)
        );

        $missingBefore = $this->dbCount(
            'SELECT COUNT(*)
               FROM methods m
               LEFT JOIN methods_similar ms ON (m.title = ms.method1_title AND m.title = ms.method2_title)
              WHERE m.title IN ('.$targetPlaceholders.')
                AND ms.method1_title IS NULL',
            $targetMethods
        );
                $this->assertSame(5, $missingBefore);

        [$exitCode, $output] = $this->executeCommand('blueline:calculateMethodSimilarities', array('methods' => $targetMethods));

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Calculating similarities', $output);
        $this->assertNoConsoleErrors($output);

        $missingAfter = $this->dbCount(
            'SELECT COUNT(*)
               FROM methods m
               LEFT JOIN methods_similar ms ON (m.title = ms.method1_title AND m.title = ms.method2_title)
              WHERE m.title IN ('.$targetPlaceholders.')
                AND ms.method1_title IS NULL',
            $targetMethods
        );
        $this->assertSame(0, $missingAfter);
    }

    public function testCalculateMethodSimilaritiesCanAbortViaConfirmationPrompt(): void
    {
        $titles = array_column(
            $this->connection->executeQuery('SELECT title FROM methods ORDER BY title ASC LIMIT 30')->fetchAllAssociative(),
            'title'
        );
        $this->assertCount(30, $titles, 'Expected at least 30 methods in the test database for interactive-branch test');

        foreach ($titles as $title) {
            $this->dbExec('DELETE FROM methods_similar WHERE method1_title = ? AND method2_title = ?', [$title, $title]);
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
            $exists = $this->dbCount('SELECT COUNT(*) FROM methods_similar WHERE method1_title = ? AND method2_title = ?', [$title, $title]);
            if ($exists === 0) {
                $this->dbExec(
                    'INSERT INTO methods_similar (method1_title, method2_title, similarity) VALUES (?, ?, 0)',
                    [$title, $title]
                );
            }
        }
    }

}
