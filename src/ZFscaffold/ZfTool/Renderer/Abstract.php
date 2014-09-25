<?php

/**
 * Created by IntelliJ IDEA.
 * User: z.wieczorek
 * Date: 04.07.14
 * Time: 09:32
 */
class ZFscaffold_ZfTool_Renderer_Abstract
{
    protected $tabAsSpaces = true;
    protected $tabSpacesSize = 4;

    /**
     * @var string Template File
     */
    protected $template;
    /**
     * @var array variables for template
     */
    protected $variables = array();
    /**
     * @var array objects for template
     */
    protected $objects = array();

    /**
     * @var  string Destination File
     */
    protected $destination;

    protected $renderedCode;

    protected $isRendered = false;

    /**
     * @var ZFscaffold_ZfTool_ScaffoldProvider
     */
    protected $provider;

    protected $forceOverWrite;

    public function __construct(ZFscaffold_ZfTool_ScaffoldProvider $provider, $forceOverWrite = false)
    {
        $this->provider = $provider;
        $this->forceOverWrite = $forceOverWrite;
    }

    /**
     * @return string
     */
    public function getDestination()
    {
        return $this->prepareDestination($this->destination);
    }

    /**
     * @param string $destination
     */
    public function setDestination($destination)
    {
        $this->destination = $destination;
    }


    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param array $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param $key
     * @param $value
     * @internal param array $variables
     */
    public function addVariable($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public function render()
    {
        $template = $this->getTemplate();
        if (Dfi_File::isReadable($template)) {
            $code = file_get_contents($template);
            $code = $this->prepare($code);
            $this->renderedCode = $code;
        } else {
            $this->provider->_printMessage('template  ' . $template . ' not readable ', ZFscaffold_ZfTool_ScaffoldProvider::MSG_ERROR);
        };
        $this->isRendered = true;
        return $this;
    }

    public function write()
    {
        if (!$this->isRendered) {
            $this->render();
        }
        return $this->provider->_createFile($this->getDestination(), $this->renderedCode, $this->forceOverWrite);


    }

    public function setObjects($objects)
    {
        $this->objects = $objects;
    }

    private function prepareDestination()
    {
        return $this->prepare($this->destination);
    }

    private function prepare($originalText)
    {
        $matches = array();
        $destination = $originalText;


        if (preg_match_all('/\{.+?\}|\$*VAR_[a-zA-Z_]+/', $destination, $matches)) {
            $matches = $matches[0];
            $matches = array_unique($matches);
            sort($matches);
            foreach ($matches as $match) {
                $key = preg_replace('/[\{\}\|]|\$*VAR_|;|\'|,/', '', $match);
                if (isset($this->variables[$key])) {
                    $destination = str_replace($match, $this->variables[$key], $destination);
                } else {
                    $this->provider->_printMessage('variable ' . $match . ' defined in ' . $this->getTemplate() . ' but not found', ZFscaffold_ZfTool_ScaffoldProvider::MSG_ERROR);
                }
            }
        }

        return $destination;
    }


    protected function getObject($key)
    {
        if (isset($this->objects[$key])) {
            return $this->objects[$key];
        } else {
            $this->provider->_printMessage('object ' . $key . ' requested in ' . $this->getTemplate() . ' but not found', ZFscaffold_ZfTool_ScaffoldProvider::MSG_ERROR);
            return '';
        }
    }


    protected function getVariable($key)
    {
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        } else {
            $this->provider->_printMessage('variable ' . $key . ' requested in ' . $this->getTemplate() . ' but not found', ZFscaffold_ZfTool_ScaffoldProvider::MSG_ERROR);
            return '';
        }
    }

    protected function formatLine($line, $tabSize = 2)
    {
        if ($this->tabAsSpaces) {
            $char = ' ';
            $tabSize = $tabSize * $this->tabSpacesSize;
        } else {
            $char = "\t";
        }
        $prefix = str_pad('', $tabSize, $char);
        return $prefix . $line;
    }

    protected function formatLineArray($lines, $tabSize = 2)
    {
        $tmp = array();
        foreach ($lines as $key => $line) {
            if ($key == 0) {
                //if (count($lines) > 1 && $key == 0) {
                $tmp[] = $line;
            } else {
                $tmp[] = $this->formatLine($line, $tabSize);
            }

        }
        return implode("\n", $tmp);
    }
}