<?php
namespace Blueline\BluelineBundle\Helpers;

use Exception;

class PgResultIterator implements \Iterator, \Countable
{
    private $result;
    private $row;
    private $position;
    private $count;

    public function __construct($result)
    {
        $this->result = $result;
        $this->count  = pg_num_rows($this->result);
        $this->rewind();
    }

    public function rewind()
    {
        $this->position = -1;
        $this->next();
    }

    public function current()
    {
        return $this->row;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        if( ++$this->position >= $this->count ) {
            $this->row = false;
        } else {
            $this->row = pg_fetch_assoc($this->result, $this->position);
        }
    }

    public function valid()
    {
        return $this->row !== false;
    }

    public function count()
    {
        return $this->count;
    }
}
