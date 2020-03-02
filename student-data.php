<?php

    class StudentsData{
        public $app_number;
        public $name;
        public $date;
        public $submit = "Yes";
        public $complete = "Pending";
        public $id;

        function __construct()
        {
            
        }

        function formatDate(){
            $arr = explode(" ", $this->date);
            return $arr[0];
        }
    }