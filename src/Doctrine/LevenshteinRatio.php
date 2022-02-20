<?php
namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * Usage: LEVENSHTEIN_RATIO(STR1, STR2)
 *
 */
class LevenshteinRatio extends FunctionNode
{
    private $firstString = null;
    private $secondString = null;

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->firstString = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->secondString = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        switch ($sqlWalker->getConnection()->getDatabasePlatform()->getName()) {
            // TODO: Implement for more platforms
            case 'postgresql':
                return '((1 - (LEVENSHTEIN('.
                    $this->firstString->dispatch($sqlWalker).', '.
                    $this->secondString->dispatch($sqlWalker).
                ')::float / '.
                'GREATEST('.
                    'CHAR_LENGTH('.$this->firstString->dispatch($sqlWalker).'),'.
                    'CHAR_LENGTH('.$this->secondString->dispatch($sqlWalker).')'.
                ')))*100)';
            case 'sqlite':
                return '((1 - (CAST(editdist3('.
                    $this->firstString->dispatch($sqlWalker).', '.
                    $this->secondString->dispatch($sqlWalker).
                ') AS REAL) / '.
                'MAX('.
                    'LENGTH('.$this->firstString->dispatch($sqlWalker).'),'.
                    'LENGTH('.$this->secondString->dispatch($sqlWalker).')'.
                ')))*100)';
        }
    }
}
