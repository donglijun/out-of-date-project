<?php
class Mkjogo_Voice_Room_Icon
{
    protected $room;
    protected $options = array();
    protected $hash;

    public function __construct($room, $options = null)
    {
        $this->room = $room;

        if ($options) {
            $this->options = $options;
        }
    }

    protected function hash()
    {
        if (!$this->hash) {
            $this->hash = md5($this->room . $this->options['salt']);
        }

        return $this->hash;
    }

    public function relativePath($data = array())
    {
        $hash = $this->hash();

        $data = array_merge(array($hash[0], $hash[1], $hash[2]), $data);

        return implode(DIRECTORY_SEPARATOR, $data);
    }

    public function absolutePath($data = array())
    {
        return implode(DIRECTORY_SEPARATOR, array(
            $this->options['path'],
            $this->relativePath($data),
        ));
    }

    public function url($data = array())
    {
        return implode(DIRECTORY_SEPARATOR, array(
            $this->options['url_prefix'],
            $this->relativePath($data),
        ));
    }
}