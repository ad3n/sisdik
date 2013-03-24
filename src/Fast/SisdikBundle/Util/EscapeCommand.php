<?php

namespace Fast\SisdikBundle\Util;
/**
 * Epson Escape Commands for direct printing to dot matrix
 * @author Ihsan Faisal
 *
 */
class EscapeCommand
{
    const ESC = "\x1B";

    private $commands = "";

    /**
     * constructor
     *
     */
    public function __construct() {
        $this->commands = "";
        $this->addResetCommand();
    }

    public function getCommands() {
        return $this->commands;
    }

    public function addResetCommand() {
        $this->commands .= self::ESC . "@";
        return $this->commands;
    }

    public function addLineSpacing_1_6() {
        $this->commands .= self::ESC . "2";
        return $this->commands;
    }

    public function addLineSpacing_1_8() {
        $this->commands .= self::ESC . "0";
        return $this->commands;
    }

    public function addLineSpacing_n_216($n) {
        $this->commands .= self::ESC . "3" . "\x$n";
        return $this->commands;
    }

    public function addPageLength33Lines() {
        $this->commands .= self::ESC . "C" . "\x21";
        return $this->commands;
    }

    public function addMarginBottom5Lines() {
        $this->commands .= self::ESC . "N" . "\x05";
        return $this->commands;
    }

    public function addMaster10CPI() {
        $this->commands .= self::ESC . "!" . "\x00";
        return $this->commands;
    }

    public function addMasterCondensed() {
        $this->commands .= self::ESC . "!" . "\x04";
        return $this->commands;
    }

    public function addModeDraft() {
        $this->commands .= self::ESC . "x" . "\x00";
        return $this->commands;
    }

    public function addModeNLQ() {
        $this->commands .= self::ESC . "x" . "\x01";
        return $this->commands;
    }

    public function addFormFeed() {
        $this->commands .= "\x0C";
        return $this->commands;
    }

    public function addContent($content) {
        $this->commands .= $content;
        return $this->commands;
    }
}
