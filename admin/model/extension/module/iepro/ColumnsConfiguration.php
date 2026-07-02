<?php
    class ColumnsConfiguration {
        /**
         * @var HttpPostParameters
         */
        private $parameters;

        /**
         * @var string
         */
        private $category_locale;

        /**
         * @var array
         */
        private $columns;

        public function __construct( HttpPostParameters $parameters, $category_locale = null) {
            $this->parameters = $parameters;
            $this->category_locale = $category_locale;

            $this->load_config();
        }

        private function load_config() {
            $this->columns = $this->parameters->get_strict( 'columns', 'Missing columns parameter');
            $this->fix_config();

            $this->category_config = $this->build_category_config();
        }

        public function exists( $name) {
            return isset( $this->columns[$name]);
        }

        public function get( $name) {
            if (!$this->exists( $name)) {
                throw new \Exception( "Column definition not found: '{$name}'");
            }

            return $this->columns[$name];
        }

        public function get_category_config() {
            return $this->category_config;
        }

        private function fix_config() {
            $this->columns = array_map( function( $column) {
                $column['custom_name'] = html_entity_decode( $column['custom_name']);
                $column['real_name'] = $column['custom_name'];

                if (isset( $column['splitted_values'])) {
                    $column['splitted_values'] = html_entity_decode( $column['splitted_values']);

                    if (!empty( $column['splitted_values']) &&
                        strpos( $column['real_name'], '>') !== false) {
                        $name_parts = explode( '>', $column['real_name']);

                        $column['real_name'] = $name_parts[0];
                        $column['value_index'] = $name_parts[1];
                    }
                }

                return $column;
            }, $this->columns);
        }

        private function build_category_config() {
            return $this->is_category_tree()
                   ? $this->build_category_tree_config()
                   : $this->build_simple_categories_config();
        }

        private function is_category_tree() {
            return $this->parameters->get_boolean( 'import_xls_category_tree');
        }

        private function build_category_tree_config() {
            $parent_count = $this->parameters->get_number( 'import_xls_cat_tree_number');
            $child_count = $this->parameters->get_number( 'import_xls_cat_tree_children_number');

            $parents = [];
            $children = [];

            for ($level = 1; $level <= $parent_count; $level++) {
                $parent_name = "Cat. tree {$level} parent";
                $parents[] = $this->get( $parent_name);

                for ($child_level = 1; $child_level <= $child_count; $child_level++) {
                    $child_name = "Cat. tree {$level} level {$child_level}";
                    $children[] = $this->get( $child_name);
                }
            }

            return (object)[
                'parents' => $parents,
                'children' => $children
            ];
        }

        private function build_simple_categories_config() {
            $categoryCount = +$this->parameters->get( 'import_xls_cat_number');
            $result = [];

            for ($i = 1; $i <= $categoryCount; $i++) {
                if ($this->category_locale !== null) {
                    $name = "Cat. {$i} {$this->category_locale}";
                } else {
                    $name = "Cat. {$i}";
                }

                $result[] = $this->get( $name);
            }

            return $result;
        }
    }
