<?php
class XML_Writer_Sphinx
{
    protected $lifo = array();

    protected $opening = false;

    public function escape($content)
    {
        return htmlspecialchars($content, ENT_QUOTES);
    }

    public function closeTag()
    {
        if ($this->opening) {
            echo ">\n";

            $this->opening = false;
        }

        return true;
    }

    public function openURI($uri)
    {
        return true;
    }

    public function setIndent($indent)
    {
        return true;
    }

    public function setIndentString($indentString)
    {
        return true;
    }

    public function startDocument($version = 1.0, $encoding = null, $standalone = null)
    {
        printf("<?xml version=\"%s\" encoding=\"%s\"?>\n", $version, $encoding ?: 'UTF-8');

        return true;
    }

    public function endDocument()
    {
        return true;
    }

    public function flush()
    {
        return true;
    }

    public function startElement($name)
    {
        $this->closeTag();

        printf('<%s', $name);

        $this->lifo[] = $name;

        $this->opening = true;

        return true;
    }

    public function endElement()
    {
        $this->closeTag();

        $name = array_pop($this->lifo);

        printf("</%s>\n", $name);

        return true;
    }

    public function text($content)
    {
        $this->closeTag();

        $content = $this->escape($content);

        printf("%s\n", $content);

        return true;
    }

    public function writeElement($name, $content)
    {
        $this->closeTag();

        $content = $this->escape($content);

        printf("<%s>%s</%s>\n", $name, $content, $name);

        return true;
    }

    public function writeAttribute($name, $value)
    {
        printf(" %s=\"%s\"", $name, $value);

        return true;
    }
}