<?php
    class CategoriesSelector {
        /**
         * @var string
         */
        private $name;

        /**
         * @var array
         */
        private $categories;

        /**
         * @var string
         */
        private $segments;

        private $value;

        public function controller( $controller) {
            $this->controller = $controller;

            return $this;
        }

        public function name( $name) {
            $this->name = $name;

            return $this;
        }

        public function categories( array $categories) {
            $this->categories = $this->sort_categories( $categories);

            return $this;
        }

        public function segments( array $segments) {
            $this->segments = $segments;

            return $this;
        }

        public function value( $value) {
            $this->value = $value;

            return $this;
        }

        public function render() {
            $html = '<select name="' . $this->name . '"
                             data-live-search="true"
                             class="selectpicker form-control">';

            $html .= '<option value="" selected>None</option>';

            foreach ($this->categories as $categoryData){
                $categoryName = $categoryData['name'];

                $catSegments = preg_split( '/\s*&gt;\s*/', $categoryName);
                $catSegments = array_map( function( $segment) {
                   return str_replace( '&nbsp;', '', $segment);
                }, $catSegments);

                $selected = '';
                $id = +$categoryData['category_id'];

                if (!empty( $this->value) && $id === +$this->value){
                    $selected = 'selected';
                }
                elseif ($this->categories_matches( $catSegments))
                {
                    $selected = 'selected';
                }

                $html .= "<option value=\"{$id}\" {$selected}>{$categoryName}</option>";
            }

            $html .= '</select>
                      <script type="text/javascript">
                        $(\'select[name="' . $this->name . '"]\').selectpicker(\'refresh\');
                      </script>';

            return $html;
        }

        private function categories_matches( $other_segments){
            if (empty( $this->segments) || count( $this->segments) !== count( $other_segments)){
                return false;
            }

            for ($i = 0; $i < count( $this->segments); $i++) {
                if (strtolower( $this->segments[$i]) !== strtolower( $other_segments[$i])) {
                    return false;
                }
            }

            return true;
        }

        private function sort_categories( array $categories) {
            usort( $categories, function( $category1, $category2) {
                return strcmp( $category1['name'], $category2['name']);
            });

            return $categories;
        }
    }
