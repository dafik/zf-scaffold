<?php

class HashableBehavior extends Behavior
{

    private $builder;

    public function objectMethods($builder)
    {

        return "
/**
 * Return object primary key hash
 *
 * @return    string Hash
 */
public function getHashable()
{
    return \\Dfi\\Crypt\\Integer::encode(\$this->getPrimaryKey());;
}
";
    }

    public function queryMethods($builder)
    {


        $this->builder = $builder;
        $script        = '';

        $this->addFilterByHashable($script);
        $this->addFindOneByHashable($script);

        return $script;
    }

    protected function addFilterByHashable(&$script)
    {
        $script .= "
/**
 * Filter the query by hash
 *
 * @param     string \$hash The value to use as filter.
 *
 * @return    " . $this->builder->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
 */
public function filterByHashable(\$hash)
{
    return \$this->filterByPrimaryKey(\\Dfi\\Crypt\\Integer::decode(\$hash));;
}
";
    }

    protected function addFindOneByHashable(&$script)
    {
        $script .= "
/**
 * Find one object based on its hash
 *
 * @param     string \$hash The value to use as filter.
 * @param     PropelPDO \$con The optional connection object
 *
 * @return    " . $this->builder->getStubObjectBuilder()->getClassname() . " the result, formatted by the current formatter
 */
public function findOneByHashable(\$hash, \$con = null)
{
    return \$this->filterByHashable(\$hash)->findOne(\$con);
}
";
    }

}
