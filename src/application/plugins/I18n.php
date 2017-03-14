<?php
class I18nPlugin extends Yaf_Plugin_Abstract
{
    const DEFAULT_LOCALE = 'en_US';

    protected $locale;

    protected $textDomain = 'default';

    protected $langs = array(
        'en' => 'en_US',
    );

    protected $messages = array();

    protected $config;

    public function __construct()
    {
        $this->config = Yaf_Registry::get('config');
    }

    protected function getScriptPath($textDomain, $module, $locale)
    {
        if (strcasecmp($module, 'index') === 0) {
            $path = sprintf('%s/locales/%s/%s.php', Yaf_Application::app()->getAppDirectory(), $locale, $textDomain);
        } else {
            $path = sprintf('%s/modules/%s/locales/%s/%s.php', Yaf_Application::app()->getAppDirectory(), $module, $locale, $textDomain);
        }

        return $path;
    }

    protected function loadMessages($textDomain, $module, $locale)
    {
        $messages = array();
        $filename = $this->getScriptPath($textDomain, $module, $locale);

        if (is_readable($filename)) {
            $messages = include $filename;
        }

//        if (!is_file($filename) || !is_readable($filename)) {
//            throw new Exception(sprintf(
//                'Could not open file %s for reading',
//                $filename
//            ));
//        }
//
//        $messages = include $filename;
//
//        if (!is_array($messages)) {
//            throw new Exception(sprintf(
//                'Expected an array, but received %s',
//                gettype($messages)
//            ));
//        }

        $this->messages[$textDomain][$module][$locale] = is_array($messages) ? $messages : array();
    }

    protected function getTranslatedMessage($message, $locale, $module, $textDomain)
    {
        if ($message === '') {
            return $message;
        }

        if (!isset($this->messages[$textDomain][$module][$locale])) {
            $this->loadMessages($textDomain, $module, $locale);
        }

        if (isset($this->messages[$textDomain][$module][$locale][$message])) {
            return $this->messages[$textDomain][$module][$locale][$message];
        }

        return null;
    }

    public function translate($message, $textDomain = null, $module = null, $locale = null)
    {
        $textDomain = $textDomain ?: $this->textDomain;
        $module = $module ?: Yaf_Dispatcher::getInstance()->getRequest()->getModuleName();
        $locale = ($locale ?: $this->getLocale());

        $translation = $this->getTranslatedMessage($message, $locale, $module, $textDomain);

        return $translation !== null ? $translation : $message;
    }

    public function setLocale($locale)
    {
        if (!is_null($locale) && $locale != $this->locale) {
            $this->locale = $locale;
        }

        return $this;
    }

    public function getLocale()
    {
        if (is_null($this->locale)) {
            $this->locale = static::DEFAULT_LOCALE;
        }

        return $this->locale;
    }

    public function setTextDomain($textDomain)
    {
        if ($textDomain != $this->textDomain) {
            $this->textDomain = $textDomain;
        }

        return $this;
    }

    public function getTextDomain()
    {
        return $this->textDomain;
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
        if ($locale = $request->get('locale')) {
            ;
        } else if (isset($this->config->i18n->locale)) {
            $locale = $this->config->i18n->locale;
        } else {
            $locale = static::DEFAULT_LOCALE;
        }

        $this->locale = $locale;
    }

    public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }

    public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    {
        ;
    }
}