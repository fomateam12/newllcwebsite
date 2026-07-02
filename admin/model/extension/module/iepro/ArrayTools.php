<?php
    class ArrayTools {
        public static function is_simple_array( $value) {
            return is_array( $value) &&
                   self::are_all_integers( array_keys( $value));
        }

        public static function all_present( array $values){
            $i = 0;
            $count = count( $values);

            while ($i < $count && !empty( $values[$i])) {
                $i++;
            }

            return $i === $count;
        }

        /**
         * @return False if statuses is empty or none of the statuses is checked.
         * @return True if $statuses is not empty and at least one of the statuses is checked
        */
        public static function any_checked( array $statuses) {
            foreach ($statuses as $status) {
                if (self::is_checked( $status)) {
                    return true;
                }
            }

            return false;
        }

        private static function is_checked( $status){
            return in_array( $status, ['1', 1]);
        }

        private static function are_all_integers( $values) {
            $i = 0;
            $count = count( $values);

            while ($i < $count && is_int( $values[$i])) {
                $i++;
            }

            return ($count > 0 && $i === $count);
        }
    }
