<?php

namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

/**
 * Custom Doctrine DQL function: REGEXP(str, regex).
 *
 * Performs database-native regex matching within DQL queries.
 * Returns boolean (use as "REGEXP(str, regex)= TRUE" in a WHERE clause).
 *
 * Platform support:
 * - PostgreSQL: Uses ~* operator (case-insensitive regex)
 * - MySQL: Uses REGEXP operator
 * - SQLite: Uses REGEXP operator
 *
 * Regular expression syntax varies by platform;
 * PostgreSQL uses POSIX extended regex, MySQL/SQLite use POSIX basic regex.
 *
 * Usage in DQL:
 * SELECT m FROM Blueline\\Entity\\Method m WHERE REGEXP(m.title, :pattern) = TRUE
 *
 * Note: This is a workaround for Doctrine's lack of custom comparison operators.
 * i.e. its inability to do "str REGEXP regex".
 *
 * @throws \RuntimeException If called on unsupported database platform
 */
class Regexp extends FunctionNode
{
    private $string;
    private $regexp;

    public function parse(\Doctrine\ORM\Query\Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->regexp = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker): string
    {
        $platform = $sqlWalker->getConnection()->getDatabasePlatform();

        if ($platform instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform) {
            return '('.
                $this->string->dispatch($sqlWalker).
                ' ~* '.
                $this->regexp->dispatch($sqlWalker).
            ')';
        }

        if ($platform instanceof \Doctrine\DBAL\Platforms\MySQLPlatform
            || $platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform
        ) {
            return '('.
                $this->string->dispatch($sqlWalker).
                ' REGEXP '.
                $this->regexp->dispatch($sqlWalker).
            ')';
        }

        throw new \RuntimeException('REGEXP function is not supported on this database platform.');
    }
}
