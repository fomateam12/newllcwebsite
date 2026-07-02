<?php
    class HttpPostParameters {
        /**
         * @var array
         */
        private $items;

        public function __construct( $request) {
            $this->items = $request->post;
        }

        public static function from( $request) {
            return new self( $request);
        }

        public function exists( $name) {
            return isset($this->items[$name]);
        }

        public function get( $nameOrNames, $default = '') {
            if (is_array( $nameOrNames)) {
               return $this->get_array( $nameOrNames);
            } else {
               return $this->get_simple( $nameOrNames, $default);
            }
        }

        public function get_number( $name, $default = 0) {
            $result = $this->get( $name, $default);

            if (!is_numeric( $result)) {
                throw new Exception( "Number expected on parameter '{$name}'");
            }

            return +$result;
        }

        public function get_boolean( $name, $default = false) {
            $result = $this->get( $name, $default);

            if (!in_array( $result, [true, false, 1, 0, '1', '0'])) {
                throw new Exception( "Boolean parameter '{$name}' expected, value found: '{$result}'");
            }

            return (bool)$result;
        }

        public function get_strict( $name, $errorMessage) {
            $result = $this->get( $name, null);

            if ($result === null) {
                die( $errorMessage);
            }

            return $result;
        }

        public function is_empty( $name) {
            return empty( $this->get( $name, null));
        }

        public function file( $name, $default = null) {
            return $this->file_exists( $name)
                   ? $_FILES[$name]
                   : $default;
        }

        public function file_exists( $name) {
            return isset( $_FILES[$name]);
        }

        public function has_file_upload() {
            $file = $this->file( 'file');

            return $file !== null && isset( $file['tmp_name']) && !empty( $file['tmp_name']);
        }

        private function get_simple( $name, $default = '') {
            $result = $default;

            if ($this->exists( $name))
            {
                $result = $this->items[$name];

                if (is_string( $result)) {
                    $result = trim( $result);
                }
            }

            return $result;
        }

        private function get_array( array $names) {
            $result = [];

            foreach ($names as $name) {
                $result[$name] = $this->get_simple( $name);
            }

            return $result;
        }
    }
