<?php

namespace Classes\Common\Util;

use Classes\Common\Logger\LoggerInterface;

/**
 * Collection of useful functions to use in projects.
 *
 * @author Felix Kastner <felix@chapterfain.com>
 */
class Util
{
    /** @var LoggerInterface $logger */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Implementation of file_get_contents using CURL
     * Will optionally use a logger.
     *
     * @param string $url url to open
     * @return mixed
     */
    public function file_get_contents_curl($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //Set curl to return the data instead of printing it to the browser.
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        //curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $data = curl_exec($ch);

        if($this->logger) {
            if(curl_errno($ch))
            {
                $this->logger->addEntry('Curl error: ' . curl_error($ch));
            } elseif(curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200) {
                $this->logger->addEntry('Curl HTML CODE: ' . curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
        }

        curl_close($ch);

        return $data;
    }
}