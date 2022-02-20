<?php
namespace Blueline\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

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

    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->string = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_COMMA);
        $this->regexp = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        switch ($sqlWalker->getConnection()->getDatabasePlatform()->getName()) {
            // TODO: Implement for more platforms
            case 'postgresql':
                return '('.
                    $this->string->dispatch($sqlWalker).
                    ' ~* '.
                    $this->regexp->dispatch($sqlWalker).
                ')';
            case 'mysql':
            case 'sqlite':
                return '('.
                    $this->string->dispatch($sqlWalker).
                    ' REGEXP '.
                    $this->regexp->dispatch($sqlWalker).
                ')';
        }
    }
}
