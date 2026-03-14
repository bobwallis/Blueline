<?php
namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

/**
 * Usage: LEVENSHTEIN(STR1, STR2)
 *
 */
class Levenshtein extends FunctionNode
{
    private $firstString = null;
    private $secondString = null;

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
