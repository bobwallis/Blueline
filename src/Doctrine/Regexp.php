<?php
namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

/**
 * Usage: REGEXP(STR, REG)
 *
 * This is a bodge to get around the inability to add custom comparison
 * operators to Doctrine.
 * Use as: "REGEXP(STR, REG) = TRUE" in a WHERE clause
 * This converts to: "(STR REGEXP REG) = TRUE"
 * Which is equivalent to "STR REGEXP REG"
 */
class Regexp extends FunctionNode
{
    private $string = null;
    private $regexp = null;

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
