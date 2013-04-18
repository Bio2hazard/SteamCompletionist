<?php

namespace Classes\Common\Template;

/**
 * Simple xml-based language template class.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class Template
{
    private $content;
    private $file;

    /**
     * Constructor.
     *
     * @param string $file
     * @param string $languageFile
     */
    public function __construct($file, $languageFile)
    {
        $this->file = $file;
        $this->content = simplexml_load_file($languageFile);
    }

    /**
     * Getter.
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->content->$name;
    }

    /**
     * Display Template
     */
    public function display()
    {
        include $this->file;
    }
}