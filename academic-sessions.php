<?php
class DWTA_Sessions{
    public $session_id;
    public $session_name;
    public $session_start;
    public $session_end;


    function __construct($id, $name, $start, $end){
        $this->session_id = $id;
        $this->session_name = $name;
        $this->session_start = $start;
        $this->session_end = $end;
    }
}