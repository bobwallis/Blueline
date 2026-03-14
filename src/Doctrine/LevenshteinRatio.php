<?php
namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\TokenType;

/**
 * Usage: LEVENSHTEIN_RATIO(STR1, STR2)
 *
 */
class LevenshteinRatio extends FunctionNode
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
            return '((1 - (LEVENSHTEIN('.
                $this->firstString->dispatch($sqlWalker).', '.
                $this->secondString->dispatch($sqlWalker).
            ')::float / '.
            'GREATEST('.
                'CHAR_LENGTH('.$this->firstString->dispatch($sqlWalker).'),'.
                'CHAR_LENGTH('.$this->secondString->dispatch($sqlWalker).')'.
            ')))*100)';
        }

        if ($platform instanceof \Doctrine\DBAL\Platforms\SqlitePlatform) {
            return '((1 - (CAST(editdist3('.
                $this->firstString->dispatch($sqlWalker).', '.
                $this->secondString->dispatch($sqlWalker).
            ') AS REAL) / '.
            'MAX('.
                'LENGTH('.$this->firstString->dispatch($sqlWalker).'),'.
                'LENGTH('.$this->secondString->dispatch($sqlWalker).')'.
            ')))*100)';
        }

        throw new \RuntimeException('LEVENSHTEIN_RATIO function is not supported on this database platform.');
    }
}
