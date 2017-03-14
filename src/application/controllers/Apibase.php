<?php
class ApibaseController extends Yaf_Controller_Abstract
{
    const FORMAT_JSON   = 'json';

    const FORMAT_JSONP  = 'jsonp';

    const FORMAT_VAR    = 'var';

    const DEFAULT_FORMAT    = 'json';

    const DEFAULT_CALLBACK  = 'mk_api_result';

    protected $authActions = array();

    protected function callback($data)
    {
        $request    = $this->getRequest();
        $format     = $request->get('format', self::DEFAULT_FORMAT);
        $callback   = $request->get('callback', self::DEFAULT_CALLBACK);

        switch ($format) {
            case self::FORMAT_JSONP:
                echo sprintf('%s(%s);', $callback, json_encode($data));
                break;
            case self::FORMAT_VAR:
                echo sprintf('var %s=%s;', $callback, json_encode($data));
                break;
            case self::FORMAT_JSON:
                echo json_encode($data);
                break;
            default:
                echo $data;
        }
    }
}