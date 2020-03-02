<?php
class Commission{
    public $amount;
    public $lower;
    public $upper;

    function __construct($a, $l, $u)
    {
        $this->amount = $a;
        $this->lower = $l;
        $this->upper = $u;   
    }
}