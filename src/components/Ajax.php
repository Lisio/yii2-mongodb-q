<?php

namespace yii\q\components;

use Yii;
use yii\base\Component;

/**
 * Ajax response class
 *
 * @property boolean $success whether request processed successfully
 * @property array $errors list of errors (field => [Error1, Error2, ...])
 * @property mixed $data response data
 */
class Ajax extends Component implements \JsonSerializable
{
    /**
     * @var boolean $success whether request processed successfully
     */
    public $success = true;

    /**
     * @var array $errors list of errors (field => [Error1, Error2, ...])
     */
    public $errors = [];

    /**
     * @var mixed $data response data
     */
    public $data = null;

    /**
     * Adds error for given field
     *
     * @param string $field field name
     * @param string $error error message
     * @return self
     */
    public function addError($field, $error)
    {
        $this->addErrors([$field => [$error]]);

        return $this;
    }

    /**
     * Adds errors and sets success param to false
     *
     * @param array $errors list of errors (field => [Error1, Error2, ...])
     * @return self
     */
    public function addErrors($errors)
    {
        $this->errors = array_merge_recursive($this->errors, $errors);

        $this->success = false;

        return $this;
    }

    /**
     * Sets response data
     *
     * @param mixed $data response data
     * @return self
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns response array for further JSON serialization
     *
     * @return array response
     */
    public function jsonSerialize()
    {
        $response = [
            'success' => $this->success,
        ];

        if ($this->success) {
            $response['data'] = $this->data;
        } else {
            $response['errors'] = $this->errors;
        }

        return $response;
    }
}
