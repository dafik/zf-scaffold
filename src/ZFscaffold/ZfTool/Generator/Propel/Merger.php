<?php

class ZFscaffold_ZfTool_Generator_Propel_Merger
{
    private $references = array();
    /**
     * @var  Zend_Config
     */
    private $config;
    private $schemaXml;
    private $ciMap;

    public function merge($schemaPath, $config)
    {

        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', true);
        defined('PROJECT_IN_ENGLISH') || define('PROJECT_IN_ENGLISH', true);
        date_default_timezone_set('Europe/Warsaw');


        $this->schemaXml = $schemaPath . '/schema.xml';
        if (Dfi\File::isReadable($config)) {
            $this->config = new Zend_Config_Ini($config);
        } else {
            $this->config = new Zend_Config(array());
        }

        $files = array();

        //$path = dirname(__FILE__) . '/../schema';

        $iterator = new DirectoryIterator($schemaPath);

        foreach ($iterator as $fileInfo) {
            /* @var $fileInfo SplFileInfo */
            if ($fileInfo->isFile()) {
                if (strpos($fileInfo->getFilename(), 'schema') !== false) {
                    $files[] = $fileInfo->getPathname();
                }
            }
        }


        foreach ($files as $schemaFile) {

            if (isset($schema)) {
                unset($schema);
            }
            $schema = new DOMDocument();
            $schema->preserveWhiteSpace = false;
            $schema->formatOutput = true;

            if (!$schema->load($schemaFile)) {
                throw new Exception(sprintf("Nie odnaleziono pliku %s.", $this->schemaXml));
            }

            $domElemsToRemove = array();


            $this->prepreConcreteInheritance($schema);

            /** @var $tables DOMNodeList */
            $tables = $schema->getElementsByTagName('table');
            /** @var $table DOMElement */
            foreach ($tables as $table) {

                if ($this->isAllowedTable($table)) {
                    $this->checkPhpName($table);
                    $this->publicSchemaFix($table, $schema);
                    $this->checkInherit($table);
                    $this->checkFk($table, $schema);

                    $this->checkConcreteInheritance($table, $schema);

                    $this->checkView($table, $schema);
                    $this->checkTree($table, $schema);
                    $this->checkManyToMany($table, $schema);

                    $this->checkManyToManyEqual($table, $schema);

                    $this->checkSortable($table, $schema);
                    $this->checkSlugable($table, $schema);
                    $this->checkHashable($table, $schema);

                    $this->checkCrossReference($table);
                } else {
                    //remove table
                    $domElemsToRemove[] = $table;
                }
            }
            foreach ($domElemsToRemove as $domElement) {
                $domElement->parentNode->removeChild($domElement);
            }

            $schema->save($schemaFile);
        }
    }

    private function isAllowedTable(DOMElement $table)
    {
        if ($this->config->get('table', false)) {
            $tables = $this->config->table;

            if ($tables->get('disallowed', false)) {
                $disallowed = $tables->disallowed;
                if ($disallowed instanceof Zend_Config) {
                    $disallowed = $disallowed->toArray();

                    $tableName = $table->getAttribute('name');
                    if (array_search($tableName, $disallowed) !== false) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    private function checkPhpName(DOMElement $table)
    {

        $phpReserved = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue',
            'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval',
            'exit', 'extends', 'final', 'finally', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof',
            'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return',
            'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor', 'yield');


        //echo '::checking names' . "\n";
        //$tableName = $table->getAttribute('name');
        $phpName = $table->getAttribute('phpName');

        $schema = $table->getAttribute('schema');

        if (false !== array_search(strtolower($phpName), $phpReserved)) {
            $phpName .= '_';
        }


        $name = $this->singularize($phpName);


        if ($schema && $schema != 'public') {
            $schema = $this->singularize($schema);

            if (!preg_match('/^' . $schema . '/i', $name)) {
                $name = ucfirst($schema) . $name;
            }
        }
        echo $phpName . '->' . $name . "\n";

        $table->setAttribute('phpName', $name);

        $columns = $table->getElementsByTagName('column');
        foreach ($columns as $column) {
            $phpName = $column->getAttribute('phpName');
            if (false !== array_search(strtolower($phpName), $phpReserved)) {
                $phpName .= '_';
                //$column->setAttribute('phpName', $phpName);
                $column->setAttribute('peerName', $phpName);
            }
            if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $phpName, $matches)) {
                $phpName = '_' . $phpName;
                $column->setAttribute('phpName', $phpName);
                $column->setAttribute('peerName', $phpName);
            }

        }
    }

    private function publicSchemaFix(DOMElement $table, DOMDocument $schema)
    {
        $tableName = $table->getAttribute('name');

        /** @var $vendorList DOMNodeList */
        if ($vendorList = $table->getElementsByTagName('vendor')) {

            $vendor = $vendorList->item(0);
            if ($vendor) {
                $type = $vendor->getAttribute('type');

                if ($type == 'pgsql') {

                    $path = sprintf("//table[@name='%s']/foreign-key", $tableName);

                    $schemaT = $table->getAttribute('schema');
                    if (!$schemaT) {
                        $table->setAttribute('schema', "public");
                    }
                    $xpath = new DOMXPath($schema);
                    $result = $xpath->evaluate($path);
                    foreach ($result as $node) {
                        $schema = $node->getAttribute('foreignSchema');
                        if (!$schema) {
                            $node->setAttribute('foreignSchema', "public");
                        }
                    }
                }
            }
        }
    }

    private function checkInherit(DOMElement $table)
    {
        $model = $this->config->model;

        if ($model) {
            $baseClass = $model->baseClass;
            $basePeer = $model->basePeer;

            //echo '::checking inheritance' . "\n";
            if ($baseClass) {
                echo "\t" . ' found:' . $baseClass . "\n";
                $table->setAttribute('baseClass', $baseClass);
            }
            if ($basePeer) {
                $table->setAttribute('basePeer', $basePeer);
                echo "\t" . ' found:' . $basePeer . "\n";
            }
        }
    }

    private function checkView(DOMElement $table, DOMDocument $schema)
    {
        ///echo '::checking views' . "\n";
        $tableName = $table->getAttribute('name');
        if (preg_match('/.*_view$/', $tableName)) {
            echo "\t" . ' found:' . $tableName . "\n";
            $table->setAttribute('skipSql', 'true');
            $table->setAttribute('readOnly', 'true');
            $path = sprintf("//table[@name='%s']/column", $tableName);
            $xpath = new DOMXPath($schema);
            /** @var $result DOMNodeList */
            $result = $xpath->evaluate($path);
            /** @var $node DOMElement */
            foreach ($result as $node) {
                $columnName = $node->getAttribute('name');
                if ($columnName == 'id') {
                    $node->setAttribute('primaryKey', "true");
                }
            }
        }
    }

    private function checkTree(DOMElement $table, DOMDocument $schema)
    {
        //echo '::checking trees' . "\n";

        $config = array();
        if ($this->config->get('tree', false)) {
            $config = $this->config->tree->toArray();
        }

        $tableName = $table->getAttribute('name');
        if (preg_match('/_tree/', $tableName) || isset($config[$tableName])) {
            echo "\t" . ' found:' . $tableName . "\n";
            //      $table->setAttribute('treeMode','NestedSet');


            $behavior = $schema->createElement('behavior');
            $behavior->setAttribute('name', 'nested_set');
            $table->appendChild($behavior);

            $leftCol = $schema->createElement('parameter');
            $leftCol->setAttribute('name', 'left_column');
            $leftCol->setAttribute('value', $config[$tableName]['lft']);

            $behavior->appendChild($leftCol);

            $rightCol = $schema->createElement('parameter');
            $rightCol->setAttribute('name', 'right_column');
            $rightCol->setAttribute('value', $config[$tableName]['rgt']);

            $behavior->appendChild($rightCol);

            $levelCol = $schema->createElement('parameter');
            $levelCol->setAttribute('name', 'level_column');
            $levelCol->setAttribute('value', $config[$tableName]['lvl']);

            $behavior->appendChild($levelCol);

            if (isset($config[$tableName]['scope']) && $config[$tableName]['scope']) {

                $useScope = $schema->createElement('parameter');
                $useScope->setAttribute('name', 'use_scope');
                $useScope->setAttribute('value', 'true');

                $behavior->appendChild($useScope);

                $scopeCol = $schema->createElement('parameter');
                $scopeCol->setAttribute('name', 'scope_column');
                $scopeCol->setAttribute('value', $config[$tableName]['scope']);

                $behavior->appendChild($scopeCol);


            }

            //[treeMode = "NestedSet|MaterializedPath"]

            /*
                <behavior name="nested_set">
                <parameter name="left_column" value="lft" />
                <parameter name="right_column" value="rgt" />
                <parameter name="level_column" value="lvl" />
                <parameter name="use_scope" value="true" />
                <parameter name="scope_column" value="thread_id" />
                </behavior>
                */

            $path = sprintf("//table[@name='%s']/column", $tableName);
            $xpath = new DOMXPath($schema);
            $result = $xpath->evaluate($path);
            /** @var $node DOMElement */
            foreach ($result as $node) {
                $columnName = $node->getAttribute('name');
                if ($columnName == 'left_col') {
                    $node->setAttribute('nestedSetLeftKey', "true");
                }
                if ($columnName == 'right_col') {
                    $node->setAttribute('nestedSetRightKey', "true");
                }
                if ($columnName == 'scope') {
                    $node->setAttribute("treeScopeKey", "true");
                }
            }
        }
    }

    private function checkManyToMany(DOMElement $table, DOMDocument $schema)
    {
        //echo '::checking manty to many' . "\n";
        $allowed = array();

        if ($this->config->get('table', false)) {
            $tables = $this->config->table;

            if ($tables->get('many2many', false)) {
                $many2many = $tables->many2many;
                if ($many2many instanceof Zend_Config) {
                    $allowed = $many2many->toArray();
                }
            }
        }

        $tableName = $table->getAttribute('name');
        if (array_search($tableName, $allowed) !== false) {
            echo "\t" . ' found:' . $tableName . "\n";
            $table->setAttribute('isCrossRef', 'true');
            $this->checkCrossForeignKeysOrder($table, $schema);
        }
    }

    private function checkCrossForeignKeysOrder(DOMElement $table, DOMDocument $schema)
    {
        $tableName = $table->getAttribute('name');

        $path = sprintf("//table[@name='%s']/column", $tableName);
        $xpath = new DOMXPath($schema);
        $columns = $xpath->evaluate($path);
        /* @var $columns DOMNodeList */

        $primaryKeys = array();
        /* @var $column DOMElement */
        foreach ($columns as $column) {

            if ($column->getAttribute('primaryKey') && $column->getAttribute('primaryKey') == 'true') {
                $primaryKeys[] = $column->getAttribute('name');
            }
        }

        $path = sprintf("//table[@name='%s']/foreign-key", $tableName);
        $xpath = new DOMXPath($schema);
        $foreign = $xpath->evaluate($path);
        /* @var $foreign DOMNodeList */

        $foreignKeys = array();
        /* @var $foreignKeys DOMNode */
        foreach ($foreign as $foreignKey) {

            /** @var $child DOMElement */
            $child = $foreignKey->firstChild;
            $foreignKeys[] = $child->getAttribute('local');
        }

        $res = $primaryKeys == $foreignKeys;
        if (!$res) {
            $first = $foreign->item(0);
            $last = $foreign->item(1);
            $lastRemoved = $table->removeChild($last);
            $table->insertBefore($lastRemoved, $first);
        }
    }

    private function checkCrossReference(DOMElement $table)
    {
        $tableName = $table->getAttribute('name');

        if (isset($this->references[$tableName])) {

            ;
        }
    }

    private function checkFk(DOMElement $table, DOMDocument $schema)
    {
        $tableName = $table->getAttribute('name');

        if ($this->config->get('fk', false)) {
            $config = $this->config->fk->toArray();
        }
        if (isset($config[$tableName])) {
            $config = $config[$tableName];
            $path = sprintf("//table[@name='%s']/foreign-key", $tableName);
            $xpath = new DOMXPath($schema);
            /** @var DOMNodeList $fk */
            $fk = $xpath->evaluate($path);

            /** @var DOMElement $fkNode */
            foreach ($fk as $fkNode) {

                $fkTable = (string)$fkNode->getAttribute("foreignTable");
                if (isset($config[$fkTable])) {
                    if (isset($config[$fkTable]['phpName'])) {
                        $fkNode->setAttribute('phpName', $config[$fkTable]['phpName']);
                    }
                }
            }
        }
    }

    private function singularize($name)
    {
        if (defined('PROJECT_IN_ENGLISH') && PROJECT_IN_ENGLISH == true) {
            // require_once('Inflector.php');
            $out = ZFscaffold_ZfTool_Generator_Propel_Inflector::singularize($name);
            //echo $name . '->' . $out . "\n";
            return $out;
        }
        return $name;
    }

    private function checkManyToManyEqual(DOMElement $table, DOMDocument $schema)
    {
        $config = array();
        if ($this->config->get('many2manyEqual', false)) {
            $config = $this->config->many2manyEqual->toArray();
        }
        $tableName = $table->getAttribute('name');
        if (isset($config[$tableName])) {


            //TODO checkparams

            echo "\t" . ' found:' . $tableName . "\n";
            $behavior = $schema->createElement('behavior');
            $behavior->setAttribute('name', 'equal_nest');
            $parameter = $schema->createElement('parameter');
            $parameter->setAttribute('name', 'parent_table');
            $parameter->setAttribute('value', 'users');

            $behavior->appendChild($parameter);

            $parameter = $schema->createElement('parameter');
            $parameter->setAttribute('name', 'reference_column_1');
            $parameter->setAttribute('value', 'user_id_1');

            $behavior->appendChild($parameter);

            $parameter = $schema->createElement('parameter');
            $parameter->setAttribute('name', 'reference_column_2');
            $parameter->setAttribute('value', 'user_id_2');

            $behavior->appendChild($parameter);


            $behavior->appendChild($parameter);
            $table->appendChild($behavior);

            /*<behavior name="equal_nest">
                <parameter name="parent_table" value="user" />
            </behavior>*/

        }
    }

    private function checkSortable(DOMElement $table, DOMDocument $schema)
    {

        $config = array();
        if ($this->config->get('sort', false)) {
            $config = $this->config->sort->toArray();
        }
        $tableName = $table->getAttribute('name');
        if (isset($config[$tableName])) {

            echo "\t" . ' found:' . $tableName . "\n";

            $params = $config[$tableName];

            $rankColumn = (isset($params['rank_column']) ? $params['rank_column'] : false);
            $scopeColumn = (isset($params['scope_column']) ? $params['scope_column'] : false);

            $sxe = new SimpleXMLElement('<behavior name="sortable"></behavior>');
            $param = $sxe->addChild('parameter');
            $param->addAttribute('name', 'rank_column');
            $param->addAttribute('value', $rankColumn);
            if ($scopeColumn) {
                $param = $sxe->addChild('parameter');
                $param->addAttribute('name', 'use_scope');
                $param->addAttribute('value', 'true');

                $param = $sxe->addChild('parameter');
                $param->addAttribute('name', 'scope_column');
                $param->addAttribute('value', $scopeColumn);

            }
            $node = dom_import_simplexml($sxe);
            $behavior = $schema->importNode($node, true);

            echo "\t" . ' found:' . $tableName . "\n";
            $table->appendChild($behavior);

            //$x = $table->ownerDocument->saveXML($table);
        }
    }

    private function checkSlugable(DOMElement $table, DOMDocument $schema)
    {
        //TODO Finish function
        $config = array();
        if ($this->config->get('slug', false)) {
            $config = $this->config->many2manyEqual->toArray();
        }
        $tableName = $table->getAttribute('name');
        if (isset($config[$tableName])) {
            $params = $config[$tableName];

            /*       $slugColumn = $params['slug_column']; //"alias"
                   $slugPattern = $params['slug_pattern']; //{Name}
                   $replacePattern = $params['replace_pattern']; ///[^\w\/]+/u
                   $replacement = $params['replacement']; //-
                   $separator = $params['separator']; //-
                   $permanent = $params['permanent']; //true
                   $scopeColumn = $params['scope_column']; //*/

            $sxe = new SimpleXMLElement('
                <behavior name="sluggable">
                    <parameter name="slug_column" value="alias" />
                    <parameter name="slug_pattern" value="{Name}" />
                    <!--<parameter name="replace_pattern" value="/[^\w\/]+/u" />-->
                    <parameter name="replacement" value="-" />
                    <parameter name="separator" value="-" />
                    <parameter name="permanent" value="true" />
                    <parameter name="scope_column" value="" />
                </behavior>'
            );
            $node = dom_import_simplexml($sxe);
            $behavior = $schema->importNode($node, true);

            echo "\t" . ' found:' . $tableName . "\n";
            $table->appendChild($behavior);

        }
    }

    private function checkHashable(DOMElement $table, DOMDocument $schema)
    {
        $allow = false;
        $disallow = false;
        if ($this->config->get('hash', false)) {
            $config = $this->config->hash->toArray();
            $allow = true;
        }
        if ($this->config->get('notHash', false)) {
            $config = $this->config->hash->toArray();
            $allow = false;
        }

        $tableName = $table->getAttribute('name');

        //$x = $table->ownerDocument->saveXML($table);

        $xpath = new DOMXPath($schema);
        $nodes = $xpath->query('column[@primaryKey="true"]', $table);

        if ($allow) {
            if (array_search($tableName, $config[$tableName]) === false && $nodes->length == 1) {
                $sxe = new SimpleXMLElement('<behavior name="hashable"/>');
                $node = dom_import_simplexml($sxe);
                $behavior = $schema->importNode($node, true);

                echo "\t" . ' found:' . $tableName . "\n";
                $table->appendChild($behavior);

            }
        } elseif ($disallow) {
            if (array_search($tableName, $config[$tableName]) === false && $nodes->length == 1) {
                $sxe = new SimpleXMLElement('<behavior name="hashable"/>');
                $node = dom_import_simplexml($sxe);
                $behavior = $schema->importNode($node, true);

                echo "\t" . ' found:' . $tableName . "\n";
                $table->appendChild($behavior);
            }
        } else {
            if ($nodes->length == 1) {
                $sxe = new SimpleXMLElement('<behavior name="hashable"/>');
                $node = dom_import_simplexml($sxe);
                $behavior = $schema->importNode($node, true);

                echo "\t" . ' found:' . $tableName . "\n";
                $table->appendChild($behavior);
            }
        }
    }

    private function checkConcreteInheritance(DOMElement $table, DOMDocument $schema)
    {
        //echo '::checking trees' . "\n";

        $config = array();
        if ($this->config->get('ci', false)) {
            $config = $this->config->ci->toArray();
        }

        $tableName = $table->getAttribute('name');
        if (isset($config[$tableName])) {
            echo "\t" . ' found:' . $tableName . "\n";

            //add behavior
            $behavior = $schema->createElement('behavior');
            $behavior->setAttribute('name', 'concrete_inheritance');
            $table->appendChild($behavior);

            $ci = $schema->createElement('parameter');
            $ci->setAttribute('name', 'extends');
            $ci->setAttribute('value', $config[$tableName]);

            $behavior->appendChild($ci);

            //remove parent fields

            $xpath = new DOMXPath($schema);

            $tmp = [];
            foreach ($this->ciMap[$config[$tableName]]["col"] as $columnName) {
                $tmp[] = '@name="' . $columnName . '"';
            }

            $qry = './column[' . implode(" or ", $tmp) . ']';
            $columns = $xpath->query($qry, $table);

            foreach ($columns as $column) {
                $column->parentNode->removeChild($column);
            }


            $tmp = [];
            foreach ($this->ciMap[$config[$tableName]]["fk"] as $fkName) {
                $tmp[] = '@foreignTable="' . $fkName . '"';
            }

            $qry = './foreign-key[' . implode(" or ", $tmp) . ']';
            $fks = $xpath->query($qry, $table);

            foreach ($fks as $fk) {
                $fk->parentNode->removeChild($fk);
            }

        }
    }

    private function prepreConcreteInheritance(DOMDocument $schema)
    {
        $map = [];

        $config = array();
        if ($this->config->get('ci', false)) {
            $mains = $this->config->ci->get('main');
            if ($mains) {
                $config = $mains->toArray();
            }
        }
        foreach ($config as $mainTable) {
            //delete descendant
            $xpath = new DOMXPath($schema);
            $tables = $xpath->query('//table[@name="' . $mainTable . '"]');

            /** @var DOMElement $table */
            foreach ($tables as $table) {
                $dcs = $xpath->query('./column[@name="descendant_class"]', $table);

                /** @var DOMElement $dc */
                foreach ($dcs as $dc) {
                    $dc->parentNode->removeChild($dc);
                }
                //prepare column map to delete on children
                $mapTable = [
                    "col" => [],
                    "fk" => []
                ];
                $columns = $xpath->query('./column', $table);
                /** @var DOMElement $column */
                foreach ($columns as $column) {
                    $mapTable["col"][] = $column->getAttribute("name");
                }

                $fks = $xpath->query('./foreign-key', $table);
                /** @var DOMElement $column */
                foreach ($fks as $fk) {
                    $mapTable["fk"][] = $fk->getAttribute("foreignTable");
                }

                $map[$table->getAttribute("name")] = $mapTable;
            }
        }
        $this->ciMap = $map;

    }
}