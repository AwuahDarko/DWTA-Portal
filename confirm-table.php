<?php
class ConfirmTable{
    public $session;
    public $name;
    public $total_reg = 0;
    public $promo = 0;
    public $regular = 0;
    public $total = 0;
    public $id;
    public $affiliate_id;
    public $complete = "No";

    function __construct($i, $s, $n , $t, $p, $r, $to, $af, $c)
    {
        $this->session = $s;
        $this->name = $n;
        $this->total_reg = $t;
        $this->promo = $p;
        $this->regular = $r;
        $this->total = $to;
        $this->id = $i;
        $this->affiliate_id = $af;
        $this->complete = $c;
    }
}