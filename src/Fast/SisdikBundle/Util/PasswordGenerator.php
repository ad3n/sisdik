<?php

namespace Fast\SisdikBundle\Util;
/**
 * 
 * @author Life.Object life.object@gmail.com
 * @link http://www.tutorialchip.com, http://www.tutorialchip.com/php-password-generator-class
 * @version 1.2, released: December 02, 2010
 */
class PasswordGenerator
{
    private $args = array(
            'length' => 8, 'alpha_upper_include' => TRUE, 'alpha_lower_include' => TRUE,
            'number_include' => TRUE, 'symbol_include' => TRUE,
    );

    private $alphaUpper = array(
            "A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q",
            "R", "S", "T", "U", "V", "W", "X", "Y", "Z"
    );
    private $alphaLower = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q",
            "r", "s", "t", "u", "v", "w", "x", "y", "z"
    );
    private $number = array(
        1, 2, 3, 4, 5, 6, 7, 8, 9
    // no zero (0)
    );
    private $symbol = array(
            "-", "_", "^", "~", "@", "&", "|", "=", "+", "!", "(", ")", "{", "}", "[", "]", ".",
            "?", "%", "*", "#"
    // no semicolon (;) and comma (,)
    );
    private $input = 4;

    private $password = 0;

    public function __construct($args = array()) {
        $this->setArgs($args);
        $this->setPassword();
    }

    /**
     * Update default arguments
     * It will update default array of class i.e $args
     * 
     * @param array $args
     * @param array $defaults
     */
    private function parseArgs($args = array(), $defaults = array()) {
        return array_merge($defaults, $args);
    }

    /**
     * set args
     * 
     * @param array $args
     */
    private function setArgs($args = array()) {
        $defaults = $this->getArgs();
        $args = $this->parseArgs($args, $defaults);
        $this->args = $args;
    }

    /**
     * Get default arguments
     * 
     * @return args array
     */
    public function getArgs() {
        return $this->args;
    }

    /**
     * Get Alpha Upper Array
     * 
     * @return alphaUpper array
     */
    private function getAlphaUpper() {
        return $this->alphaUpper;
    }

    /**
     * Get Alpha Lower Array
     * 
     * @return alphaLower array
     */
    private function getAlphaLower() {
        return $this->alphaLower;
    }

    /**
     * Get Number Array
     * 
     * @return number array
     */
    private function getNumber() {
        return $this->number;
    }

    /**
     * Get Symbol Array
     * 
     * @return symbol array
     */
    private function getSymbol() {
        return $this->symbol;
    }

    /**
     * Generate Password
     * 
     */
    private function setPassword() {
        $temp = array();
        $exec = array();

        $args = $this->getArgs();
        extract($args);

        /* Minimum Validation */
        if ($length <= 0) {
            $this->password = 0;
            return 0;
        }

        /* Alpha Upper */
        if ($alpha_upper_include === TRUE) {
            $alpha_upper = $this->getAlphaUpper();
            $exec[] = 1;
        }

        /* Alpha Lower */
        if ($alpha_lower_include === TRUE) {
            $alpha_lower = $this->getAlphaLower();
            $exec[] = 2;
        }

        /* Number */
        if ($number_include === TRUE) {
            $number = $this->getNumber();
            $exec[] = 3;
        }

        /* Symbol */
        if ($symbol_include === TRUE) {
            $symbol = $this->getSymbol();
            $exec[] = 4;
        }

        /* Unique and Random Loop */
        $exec_count = count($exec) - 1;
        $input_index = 0;

        for ($i = 1; $i <= $length; $i++) {
            switch ($exec[$input_index]) {
                case 1:
                    shuffle($alpha_upper);
                    $temp[] = $alpha_upper[0];
                    unset($alpha_upper[0]);
                    break;
                case 2:
                    shuffle($alpha_lower);
                    $temp[] = $alpha_lower[0];
                    unset($alpha_lower[0]);
                    break;
                case 3:
                    shuffle($number);
                    $temp[] = $number[0];
                    unset($number[0]);
                    break;
                case 4:
                    shuffle($symbol);
                    $temp[] = $symbol[0];
                    unset($symbol[0]);
                    break;
            }

            if ($input_index < $exec_count) {
                $input_index++;
            } else {
                $input_index = 0;
            }
        }

        /* Shuffle */
        shuffle($temp);

        /* Make Password */
        $password = implode($temp);

        $this->password = $password;
    }

    public function getPassword() {
        return $this->password;
    }
}
