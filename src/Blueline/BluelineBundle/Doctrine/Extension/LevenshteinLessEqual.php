<?php
namespace Blueline\BluelineBundle\Doctrine\Extension;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * Usage: LEVENSHTEIN_LESS_EQUAL(STR1, STR2, LIMIT)
 *
 */
class LevenshteinLessEqual extends FunctionNode
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
        $parser->match(Lexer::T_COMMA);
        $this->limit = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        switch ($sqlWalker->getConnection()->getDatabasePlatform()->getName()) {
            // TODO: Implement for more platforms
            case 'postgresql':
                return 'LEVENSHTEIN_LESS_EQUAL('.
                    $this->firstString->dispatch($sqlWalker).', '.
                    $this->secondString->dispatch($sqlWalker).', '.
                    $this->limit->dispatch($sqlWalker).
                ')';
        }
    }
}
