<?php

class XajaxResponse
{
    public $assigned = array();

    public function assign($id, $attribute, $value)
    {
        $this->assigned[$id][$attribute] = $value;
    }

    public function script($script)
    {
    }
}
