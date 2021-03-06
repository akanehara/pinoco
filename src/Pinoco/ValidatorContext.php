<?php
/**
 * Pinoco: makes existing static web site dynamic transparently.
 * Copyright 2010-2011, Hisateru Tanaka <tanakahisateru@gmail.com>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * PHP Version 5
 *
 * @category   Framework
 * @author     Hisateru Tanaka <tanakahisateru@gmail.com>
 * @copyright  Copyright 2010-2011, Hisateru Tanaka <tanakahisateru@gmail.com>
 * @license    MIT License (http://www.opensource.org/licenses/mit-license.php)
 * @version    0.5.2
 * @link       https://github.com/tanakahisateru/pinoco
 * @filesource
 * @package    Pinoco
 */

/**
 * @package Pinoco
 * @property-read string $value Validated value.
 * @property-read string $test Reason of failed by test name.
 * @property-read boolean $valid Totally valid.
 * @property-read boolean $invalid Totally invalid.
 * @property-read string $message Error message when invalid.
 */
class Pinoco_ValidatorContext extends Pinoco_DynamicVars {
    
    private $_validator;
    private $_name;
    private $_label;
    
    private $_filtered;
    private $_filteredValue;
    
    private $_valid;
    private $_test;
    private $_message;
    
    /**
     * Constructor
     * @param Pinoco_Validator $validator
     * @param string $name
     * @param string $label
     */
    public function __construct($validator, $name, $label=false)
    {
        parent::__construct();
        $this->_validator = $validator;
        $this->_name = $name;
        $this->_label = $label ? $label : $name;
        
        $this->_filtered = false;
        $this->_filteredValue = null;
        
        $this->_valid = true;
        $this->_test = null;
        $this->_message = null;
    }
    
    /**
     * Test target value.
     * @return mixed
     */
    public function get_value()
    {
        if($this->_filtered) {
            return $this->_filteredValue;
        }
        else {
            if(($r = $this->_validator->fetchExistenceAndValue($this->_name)) === null) {
                return null;
            }
            list($exists, $value) = $r;
            return $exists ? $value : null;
        }
    }
    
    /**
     * Failed test.
     * @return string
     */
    public function get_test()
    {
        return $this->_test;
    }
    
    /**
     * is valid or not.
     * @return boolean
     */
    public function get_valid()
    {
        return $this->_valid;
    }
    
    /**
     * inverse of valid.
     * @return boolean
     */
    public function get_invalid()
    {
        return !$this->_valid;
    }

    /**
     * Error message for the first failed check.
     * @return string
     */
    public function get_message()
    {
        return $this->_message;
    }
    
    private function buildMessage($template, $param, $value, $label)
    {
        if(is_callable($template)) {
            return call_user_func($template, $param, $value, $label);
        }
        if(is_string($template)) {
            return str_replace(
                array('{param}', '{value}', '{label}'),
                array(strval($param), strval($value), $label),
                $template
            );
        }
    }
    
    /**
     * Check the field by specified test.
     * @param string $test
     * @param string $message
     * @return Pinoco_ValidatorContext
     */
    public function is($test, $message=false)
    {
        if(!$this->_valid) {
            return $this;
        }
        $param = explode(' ', trim($test));
        $testName = array_shift($param);
        $param = count($param) == 0 ? null : implode(' ', $param);
        list($result, $value) = $this->_validator->execValidityTest(
            $this->_name, $this->_filtered, $this->_filteredValue, $testName, $param
        );
        if(!$result) {
            $this->_test = $test;
            $this->_valid = false;
            $template = $message ? $message : $this->_validator->getMessageFor($testName);
            $this->_message = $this->buildMessage($template, $param, $value, $this->_label);
        }
        return $this;
    }
    
    /**
     * Converts value format for trailing statements.
     * @param mixed $filter
     * @return Pinoco_ValidatorContext
     */
    public function filter($filter)
    {
        if(!$this->_valid) {
            return $this;
        }
        $param = explode(' ', trim($filter));
        $filterName = array_shift($param);
        $param = count($param) == 0 ? null : implode(' ', $param);
        list($filtered, $value) = $this->_validator->execFilter(
            $this->_name, $this->_filtered, $this->_filteredValue, $filterName, $param
        );
        if($filtered) {
            $this->_filtered = $this->_filtered || true;
            $this->_filteredValue = $value;
        }
        return $this;
    }
}
