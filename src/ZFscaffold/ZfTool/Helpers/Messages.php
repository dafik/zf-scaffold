<?php

class ZFscaffold_ZfTool_Helpers_Messages
{
    const MSG_NORMAL = 'normal';
    const MSG_ERROR = 'error';
    const MSG_SPECIAL = 'special';

    private static $colorSupport;

    /**
     * @var Zend_Tool_Framework_Client_Response
     */
    private static $response;


    /**
     * @param $messages
     * @param $mode
     * @param array $options
     */
    public static function printOut($messages, $mode = self::MSG_NORMAL, $options = array())
    {
        if (null === self::$colorSupport) {
            $color_numbers = @exec('tput colors');
            if (empty($color_numbers)) {
                self::$colorSupport = false;
            } else {
                self::$colorSupport = true;
            }
        }


        if (!self::$response) {
            echo 'no respons object was set' . "\n";
            echo $mode . "\n";
        }


        if (!is_array($messages)) {
            $tmp = $messages;
            $messages = array();
            $messages[] = $tmp;
        }

        foreach ($messages as $key => $message) {
            if (is_array($message)) {
                $tmp = $message;
                $message = $tmp[0];
                if (count($tmp) > 1) {
                    $mode = $tmp[1];
                    if (count($tmp) > 2) {
                        $options = $tmp[2];
                    }
                }
            }
            if (!self::$colorSupport) {
                self::$response->appendContent($message);
            } else {
                switch ($mode) {
                    case self::MSG_ERROR:
                        $width = @exec('tput cols');
                        self::$response->appendContent($message, array('color' => array('hiWhite', 'bgRed'), 'aligncenter' => !empty($width) ? $width : 80));
                        if ($key == count($messages) - 1) {
                            self::$response->appendContent(PHP_EOL);
                        }
                        break;
                    case self::MSG_SPECIAL:
                        self::$response->appendContent($message, $options);
                        break;
                    case self::MSG_NORMAL:
                    default:
                        self::$response->appendContent($message);
                        break;
                }
            }
        }
    }

    /**
     * @param Zend_Tool_Framework_Client_Response $response
     */
    public static function setResponse(Zend_Tool_Framework_Client_Response $response)
    {
        self::$response = $response;
    }
}