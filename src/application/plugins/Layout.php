<?php
class LayoutPlugin extends Yaf_Plugin_Abstract
{
    const SET       = 'SET';
    const APPEND    = 'APPEND';
    const PREPEND   = 'PREPEND';

    private $path;
    private $name       = 'layout';
    private $enabled    = true;
    private $vars       = array();
    private $headTitle  = array();
    private $headMeta   = array();
    private $headScript = array();
    private $headStyle  = array();
    private $headLink   = array();

    private $otherBody  = null;

    public function __construct()
    {
        $config = Yaf_Registry::get('config');

        $this->path = !empty($config->layout->path) ? $config->layout->path : APPLICATION_PATH . '/application/views';
        $this->name = !empty($config->layout->name) ? $config->layout->name : 'layout';
    }

    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function setPath($value)
    {
        if (is_dir($value)) {
            $this->path = $value;
        }

        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setName($value)
    {
        $this->name = $value;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTpl()
    {
        $config = Yaf_Registry::get('config');

        return $this->path . DIRECTORY_SEPARATOR . $this->name . '.' . $config->application->view->ext;
    }

    public function disableLayout()
    {
        $this->enabled = false;

        return $this;
    }

    public function addHeadTitle($title = null, $setType = null)
    {
        $title = (string) $title;
        if ($title !== '') {
            if ($setType == self::SET) {
                $this->headTitle = array($title);
            } else if ($setType == self::PREPEND) {
                array_unshift($this->headTitle, $title);
            } else {
                $this->headTitle[] = $title;
            }
        }

        return $this;
    }

    public function getHeadTitle($separator = '-')
    {
        return '<title>' . Misc::escape(implode($separator, array_reverse($this->headTitle))) . '</title>';
    }

    public function addHeadMeta($data = null, $setType = null)
    {
        if (is_array($data))
        {
            if ($setType == self::SET) {
                $this->headMeta = array($data);
            } else if ($setType == self::PREPEND) {
                array_unshift($this->headMeta, $data);
            } else {
                $this->headMeta[] = $data;
            }
        }

        return $this;
    }

    public function getHeadMeta($separator = "\n")
    {
        $data = array();

        foreach ($this->headMeta as $meta) {
            $data[] = '<meta ' . Misc::formatKeyValue($meta) . '>';
        }

        return implode($separator, $data);
    }

    public function addHeadScript($data = null, $setType = null)
    {
        if (is_array($data)) {
            if (!isset($data['type'])) {
                $data['type'] = 'text/javascript';
            }

            if ($setType == self::SET) {
                $this->headScript = array($data);
            } else if ($setType == self::PREPEND) {
                array_unshift($this->headScript, $data);
            } else {
                $this->headScript[] = $data;
            }
        }

        return $this;
    }

    public function getHeadScript($separator = "\n")
    {
        $data = array();

        foreach ($this->headScript as $script) {
            $data[] = '<script ' . Misc::formatKeyValue($script) . '>';
        }

        return implode($separator, $data);
    }

    public function addHeadStyle($data = null, $setType = null)
    {
        if (is_array($data)) {
            if (!isset($data['type'])) {
                $data['type'] = 'text/css';
            }
            if (!isset($data['rel'])) {
                $data['rel'] = 'stylesheet';
            }

            if ($setType == self::SET) {
                $this->headStyle = array($data);
            } else if ($setType == self::PREPEND) {
                array_unshift($this->headStyle, $data);
            } else {
                $this->headStyle[] = $data;
            }
        }

        return $this;
    }

    public function getHeadStyle($separator = "\n")
    {
        $data = array();

        foreach ($this->headStyle as $style) {
            $data[] = '<link ' . Misc::formatKeyValue($style) . '>';
        }

        return implode($separator, $data);
    }

    public function addHeadLink($data = null, $setType = null)
    {
        if (is_array($data)) {
            if ($setType == self::SET) {
                $this->headLink = array($data);
            } else if ($setType == self::PREPEND) {
                array_unshift($this->headLink, $data);
            } else {
                $this->headLink[] = $data;
            }
        }

        return $this;
    }

    public function getHeadLink($separator = "\n")
    {
        $data = array();

        foreach ($this->headLink as $link) {
            $data[] = '<link ' . Misc::formatKeyValue($link) . '>';
        }

        return implode($separator, $data);
    }

    public function displayOther($body)
    {
        $this->otherBody = $body;
    }

    public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }

    public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }

    public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }

    public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        if ($request->isXmlHttpRequest()) {
            Yaf_Dispatcher::getInstance()->disableView();
            Yaf_Dispatcher::getInstance()->autoRender(false);

            $this->disableLayout();
        }
    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        if ($this->enabled) {
            if ($this->otherBody) {
                $body = $this->otherBody;
            } else {
                $body = $response->getBody();
            }

            $response->clearBody();

            $layout = new Yaf_View_Simple($this->path);
            $layout->tpl_content = $body;
            $layout->assign(array(
                'layout'    => $this->vars,
            ));

            $response->setBody($layout->render($this->getTpl()));
        }
    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }
}