<?php

namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom Doctrine DQL function: LEVENSHTEIN(str1, str2).
 *
 * Calculates Levenshtein distance (edit distance) between two strings.
 * Returns the minimum number of single-character edits (insertions, deletions, substitutions)
 * required to change one string into another.
 *
 * Platform support:
 * - PostgreSQL: Uses native LEVENSHTEIN() function (pgcontrib fuzzystrmatch extension)
 * - SQLite: Uses editdist3() function
 * - MySQL: NOT SUPPORTED (would need implementation)
 *
 * Usage in DQL:
 * SELECT m FROM Blueline\\Entity\\Method m WHERE LEVENSHTEIN(m.title, :searchTerm) < 3
 *
 * @throws \RuntimeException If called on unsupported database platform
 *
 * @see https://www.postgresql.org/docs/current/fuzzystrmatch.html
 */
class Levenshtein extends FunctionNode
{
    private $firstString;
    private $secondString;

    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->firstString = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->secondString = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            return 'LEVENSHTEIN('.
                $this->firstString->dispatch($sqlWalker).', '.
                $this->secondString->dispatch($sqlWalker).
            ')';
        }

        if ($platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
            return 'editdist3('.
                $this->firstString->dispatch($sqlWalker).', '.
                $this->secondString->dispatch($sqlWalker).
            ')';
        }

        throw new \RuntimeException('LEVENSHTEIN function is not supported on this database platform.');
    }
}
