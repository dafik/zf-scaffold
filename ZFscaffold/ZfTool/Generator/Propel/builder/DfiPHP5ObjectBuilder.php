<?php


require_once 'builder/om/PHP5ObjectBuilder.php';

class DfiPHP5ObjectBuilder extends PHP5ObjectBuilder
{
    /**
     * Adds the comment for a mutator
     * @param string &$script The script will be modified in this method.
     * @param Column $col The current column.
     * @see        addMutatorOpen()
     **/
    public function addMutatorComment(&$script, Column $col)
    {
        $clo = strtolower($col->getName());
        $script .= "
    /**
     * Set the value of [$clo] column.
     * " . $col->getDescription() . "
     * @param " . $col->getPhpType() . "|null \$v new value
     * @return " . $this->getObjectClassname() . " The current object (for fluent API support)
     */";
    }
} // PHP5ObjectBuilder
