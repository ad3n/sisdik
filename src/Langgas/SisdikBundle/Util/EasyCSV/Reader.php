<?php

namespace Langgas\SisdikBundle\Util\EasyCSV;
class Reader extends AbstractBase
{
    private $_headers;
    private $_line;
    private $_delimiter = ',';

    public function __construct($path, $mode = 'r+', $delimiter = ',') {
        parent::__construct($path, $mode);
        $this->_delimiter = $delimiter;
        $this->_headers = $this->formatHeaders($this->getRow());
        $this->_line = 0;
    }

    public function getRow() {
        if (($row = fgetcsv($this->_handle, 1000, $this->_delimiter, $this->_enclosure)) !== false) {
            $this->_line++;
            // return $row;
            return $this->_headers ? array_combine($this->_headers, $row) : $row;
        } else {
            return false;
        }
    }

    public function getAll() {
        $data = array();
        while ($row = $this->getRow()) {
            $data[] = $row;
        }
        return $data;
    }

    public function getLineNumber() {
        return $this->_line;
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function formatHeaders($row) {
        $headers = array();
        foreach ($row as $k => $v) {
            $headers[] = $this->toCamelCase($v);
        }

        return $headers;
    }

    public function toCamelCase($str) {
        $str = ucfirst($str);
        $func = create_function('$c', 'return strtoupper($c[1]);');

        return preg_replace_callback('/_([a-z])/', $func, $str);
    }
}
