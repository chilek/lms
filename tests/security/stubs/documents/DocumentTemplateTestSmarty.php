<?php

class DocumentTemplateTestSmarty
{
    public $assigned = array();

    public function assign($name, $value)
    {
        $this->assigned[$name] = $value;
    }

    public function fetch($name)
    {
        return $name;
    }
}
