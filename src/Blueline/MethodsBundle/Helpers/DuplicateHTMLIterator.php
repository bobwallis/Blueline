<?php
namespace Blueline\MethodsBundle\Helpers;

use \Exception;

class DuplicateHTMLIterator implements \Iterator
{
    private $handle;
    private $line;
    private $position;

    public function __construct($file)
    {
        if ( ( $this->handle = fopen( $file, 'r' ) ) == false ) { return false; }
        $this->rewind();
    }

    public function rewind()
    {
        rewind( $this->handle );
        // Advance to the actual start of the data
        while ( $line = fgets( $this->handle ) ) {
            if ( strpos( $line, '<p><b><i>' ) === 0 ) {
                $this->line = $line;
                break;
            }
        }
    }

    public function current()
    {
        preg_match( '/^<p><b><i>(.*?)<\/i><\/b> .*?(on|at|as) (.*?) on (.*?) (was|(\(.*\)?) was) <b><i>(.*?)<\/i>/', $this->line, $matches );

        if ($matches == null) {
            throw new Exception( "Failed to match on: {$line}" );
        }
        $data = array(
            'id'       => hash( 'md5', $this->line ),
            'title'    => html_entity_decode( $matches[1] ),
            'location' => html_entity_decode( $matches[3] ),
            'date'     => new \DateTime( date( 'Y-m-d', strtotime( $matches[4] ) ) ),
            'rwRef'    => trim( $matches[6], '()' ),
        );

        $rungExplode = explode( ' ', $matches[1] );
        $stage = array_pop( $rungExplode );
        $classification = array_pop( $rungExplode );
        $little = false;
        $differential = false;

        $last = array_pop( $rungExplode );
        switch ($last) {
            case 'Little':
                $little = true;
                break;
            case 'Differential':
                $differential = true;
                break;
        }
        $last = array_pop( $rungExplode );
        if ($last === 'Differential') {
            $differential = true;
        }
        $data['method'] = str_replace( '’', "'", html_entity_decode( $matches[7] ) ).($differential?' Differential':'').($little?' Little':'').' '.$classification.' '.$stage;

        return $data;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        $this->line = fgets( $this->handle );
    }

    public function valid()
    {
        return (strpos( $this->line, '<p><b><i>' ) === 0);
    }
}