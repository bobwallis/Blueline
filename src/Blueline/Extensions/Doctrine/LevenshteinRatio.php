<?php
namespace Blueline\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode,
    Doctrine\ORM\Query\Lexer;

/**
 * Usage: LEVENSHTEIN_RATIO(STR1, STR2)
 *
 * Install the UDF in /resources/UDFs/
 */
class LevenshteinRatio extends FunctionNode {
	private $firstString = null;
	private $secondString = null;	
	
	public function parse( \Doctrine\ORM\Query\Parser $parser ) {
		$parser->match( Lexer::T_IDENTIFIER );
		$parser->match( Lexer::T_OPEN_PARENTHESIS );
		$this->firstString = $parser->ArithmeticPrimary();
		$parser->match( Lexer::T_COMMA );
		$this->secondString = $parser->ArithmeticPrimary();
		$parser->match( Lexer::T_CLOSE_PARENTHESIS );
	}

	public function getSql( \Doctrine\ORM\Query\SqlWalker $sqlWalker ) {
		return 'LEVENSHTEIN_RATIO(' .
			$this->firstString->dispatch( $sqlWalker ) . ', ' .
			$this->secondString->dispatch( $sqlWalker ) .
		')';
	}
}
