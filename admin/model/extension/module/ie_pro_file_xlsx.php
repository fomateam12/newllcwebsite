<?php
    //Devman Extensions - info@devmanextensions.com - 2017-01-20 16:33:18 - Excel library
        require_once DIR_SYSTEM . 'library/Spout/Autoloader/autoload.php';
        use Box\Spout\Reader\ReaderFactory;
        use Box\Spout\Writer\WriterFactory;
        use Box\Spout\Common\Type;
        use Box\Spout\Writer\Style\StyleBuilder;
        use Box\Spout\Writer\Style\Color;
        use Box\Spout\Writer\Style\Border;
        use Box\Spout\Writer\Style\BorderBuilder;
    //END

    class ModelExtensionModuleIeProFileXlsx extends ModelExtensionModuleIeProFile {
        public function __construct($registry){
            parent::__construct($registry);
        }
        function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;
            $this->writer = WriterFactory::create(Type::XLSX);
            $this->writer->openToFile($this->filename_path);
        }
        function insert_columns($columns) {
            $firstSheet = $this->writer->getCurrentSheet();
            $sheet_name = substr($this->language->get('xlsx_sheet_name_'.$this->profile['import_xls_i_want']), 0, 31);
            $firstSheet->setName($sheet_name);

            $final_column_names = array();
            $columns = $this->set_column_bg_color($columns);

            $styles_array = array();
            foreach ($columns as $key2 => $col) {
                if(!array_key_exists($col['bg_color'], $styles_array))
                    $styles_array[$col['bg_color']] = $this->get_style_cell($col['bg_color']);

                $final_column_names[] = array(
                    'value' => $col['custom_name'],
                    'style' => $styles_array[$col['bg_color']]
                );
            }

            $this->writer->addRowWithStyle($final_column_names, $this->get_style_cell());
        }

        function insert_data($columns, $elements) {
            $elements_to_insert = count($elements);
            $style = $this->get_style_cell_simple();
            $count = 0;
            $message = sprintf($this->language->get('progress_export_elements_inserted'), 0, $elements_to_insert);
            $this->update_process($message);
            foreach ($elements as $element_id => $element) {
                $temp = array();
                foreach ($columns as $col_name => $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $temp[] = array_key_exists($custom_name, $element) ? $element[$custom_name] : '';
                }
                $this->writer->addRowWithStyle($temp, $style);
                $count++;
                $message = sprintf($this->language->get('progress_export_elements_inserted'), $count, $elements_to_insert);
                $this->update_process($message, true);
            }
            $this->writer->close();
        }

        function insert_data_multisheet($data) {
            $first_sheet = true;
            $style = $this->get_style_cell('30c5f0');
            foreach ($data as $sheet_name => $sheet_data) {
                if($first_sheet) {
                    $this->writer->getCurrentSheet();
                    $first_sheet = false;
                } else
                    $this->writer->addNewSheetAndMakeItCurrent();

                $currentSheet = $this->writer->getCurrentSheet();
                $currentSheet->setName($sheet_name);

                //<editor-fold desc="Insert columns">
                    $final_column_names = array();
                    foreach ($sheet_data['columns'] as $key2 => $col) {
                        $final_column_names[] = array(
                            'value' => $col,
                            'style' => $style
                        );
                    }
                    $this->writer->addRowWithStyle($final_column_names, $this->get_style_cell());
                //</editor-fold>

                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $sheet_name);
                $this->update_process($message);

                $this->writer->addRows($sheet_data['data']);
            }
            $this->writer->close();
        }

        function get_data( $progress_update = true) {
            $reader = ReaderFactory::create(Type::XLSX);
            $reader->open($this->file_tmp_path);

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            $rows = 0;

            $sheet_current = 1;

            if ($progress_update) {
                $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));
            }

            $column_stars_at_row = 1;

            if(is_file($this->assets_path.'model_ie_pro_file_xlsx_get_data_change_row_start.php'))
                require($this->assets_path.'model_ie_pro_file_xlsx_get_data_change_row_start.php');

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $key => $row) {
                    $rows++;

                    if ($progress_update) {
                        $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows), true);
                    }

                    if($key < $column_stars_at_row)
                        continue;

                    if ($key == $column_stars_at_row) {
                        $columns_only_spaces = array();
                        foreach ($row as $col_numb => $col) {
                           if (strlen($col) > 0 && strlen(trim($col)) == 0)
                               $columns_only_spaces[] = $col_numb+1;
                        }
                        if(!empty($columns_only_spaces))
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode($columns_only_spaces, ',')));

                        $final_excel['columns'] = $row;
                    } else {
                        if (!empty(array_filter($row))) {
                            foreach ($row as $key2 => $dat) {
                                if (is_a($dat, 'DateTime')) {
                                    $temp = $dat->format('Y-m-d');
                                    $row[$key2] = $temp;
                                }
                            }
                            $final_excel['data'][] = $row;
                        }
                    }
                }
                //ONLY FIRST SHEET FOR NOW
                break;
            }

            if(is_file($this->assets_path.'model_ie_pro_file_xlsx_after_get_data.php'))
                require($this->assets_path.'model_ie_pro_file_xlsx_after_get_data.php');

            return $final_excel;
        }

        public function get_data_multisheet() {
            $reader = ReaderFactory::create(Type::XLSX);
            $reader->open($this->file_tmp_path);

            $final_excel = array();

            $rows = 0;

            $sheet_current = 1;
            $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));

            foreach ($reader->getSheetIterator() as $sheet) {
                $table = $sheet->getName();
                $final_excel[$table] = array();
                foreach ($sheet->getRowIterator() as $key => $row) {
                    $rows++;
                    $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows), true);
                    if ($key == 1) {
                        $columns_only_spaces = array();
                        foreach ($row as $col_numb => $col) {
                           if (strlen($col) > 0 && strlen(trim($col)) == 0)
                               $columns_only_spaces[] = $col_numb+1;
                        }
                        if(!empty($columns_only_spaces))
                            $this->exception(sprintf($this->language->get('progress_import_error_columns_spaces'), implode($columns_only_spaces, ',')));

                        $final_excel[$table]['columns'] = $row;
                    } else {
                        if (!empty(array_filter($row))) {
                            foreach ($row as $key2 => $dat) {
                                if (is_a($dat, 'DateTime')) {
                                    $temp = $dat->format('Y-m-d');
                                    $row[$key2] = $temp;
                                }
                            }
                            $final_excel[$table]['data'][] = $row;
                        }
                    }
                }
            }
            return $final_excel;
        }

        function get_style_cell($background_color = '55acee') {
            $border = $this->get_border_cell();

            return (new StyleBuilder())
                ->setBorder($border)
                ->setFontBold()
                ->setFontSize(11)
                ->setFontColor('ffffff')
                ->setShouldWrapText(false)
                ->setBackgroundColor($background_color)
                ->build();
        }

        function get_style_cell_simple() {
            $border = $this->get_border_cell();

            return (new StyleBuilder())
            ->setBorder($border)
            ->setShouldWrapText(false)
            ->build();
        }

        function get_border_cell() {
            return (new BorderBuilder())
                ->setBorderTop('000000', Border::WIDTH_THIN)
                ->setBorderBottom('000000', Border::WIDTH_THIN)
                ->setBorderLeft('000000', Border::WIDTH_THIN)
                ->setBorderRight('000000', Border::WIDTH_THIN)
                ->build();
        }

        function set_column_bg_color($columns) {
            $array_styles = array('30c5f0', '31869b', '60497a', 'e26b0a', 'c0504d', '9bbb59', '948a54', '4f6228', '1f497d', '494529', '30c5f0', '403151', 'a6a6a6', '974706', '595959', '922a96');

            foreach ($columns as $col_name => $col_info) {
                if($this->profile['import_xls_i_want'] != 'products')
                    $columns[$col_name]['bg_color'] = $array_styles[0];
                else {
                    switch ($col_name) {
                        case strstr($col_name, 'Model'):
                        case strstr($col_name, 'Name'):
                        case strstr($col_name, 'Description'):
                        case strstr($col_name, 'Attribute group id'):
                        case strstr($col_name, 'Attribute id'):
                        case strstr($col_name, 'Manufacturer id'):
                        case strstr($col_name, 'Manufacturer image'):
                        case strstr($col_name, 'Filter Group id'):
                            $columns[$col_name]['bg_color'] = $array_styles[0];
                            break;

                        case strstr($col_name, 'Meta description'):
                        case strstr($col_name, 'Meta title'):
                        case strstr($col_name, 'Meta H1'):
                        case strstr($col_name, 'Meta keywords'):
                        case strstr($col_name, 'SEO url'):
                        case strstr($col_name, 'Tags'):
                            $columns[$col_name]['bg_color'] = $array_styles[1];
                            break;

                        case strstr($col_name, 'SKU'):
                        case strstr($col_name, 'EAN'):
                        case strstr($col_name, 'UPC'):
                        case strstr($col_name, 'JAN'):
                        case strstr($col_name, 'MPN'):
                        case strstr($col_name, 'ISBN'):
                            $columns[$col_name]['bg_color'] = $array_styles[2];
                            break;

                        case strstr($col_name, 'Minimum'):
                        case strstr($col_name, 'Subtract'):
                        case strstr($col_name, 'Out stock status'):
                            $columns[$col_name]['bg_color'] = $array_styles[3];
                            break;

                        case strstr($col_name, 'Price'):
                        case strstr($col_name, 'Quantity'):
                        case strstr($col_name, 'Points'):
                        case strstr($col_name, 'Tax class'):
                            $columns[$col_name]['bg_color'] = $array_styles[5];
                            break;

                        case strstr($col_name, 'Option'):
                            $columns[$col_name]['bg_color'] = $array_styles[4];
                            break;

                        case strstr($col_name, 'Spe. '):
                            $columns[$col_name]['bg_color'] = $array_styles[6];
                            break;

                        case strstr($col_name, 'Dis. '):
                            $columns[$col_name]['bg_color'] = $array_styles[7];
                            break;

                        case strstr($col_name, 'Manufacturer'):
                        case strstr($col_name, 'Cat.'):
                        case strstr($col_name, 'Main category'):
                            $columns[$col_name]['bg_color'] = $array_styles[8];
                            break;

                        case strstr($col_name, 'Main image'):
                        case strstr($col_name, 'Image'):
                            $columns[$col_name]['bg_color'] = $array_styles[9];
                            break;

                        case strstr($col_name, 'Date available'):
                        case strstr($col_name, 'Requires shipping'):
                        case strstr($col_name, 'Location'):
                        case strstr($col_name, 'Sort order'):
                        case strstr($col_name, 'Store'):
                        case strstr($col_name, 'Status'):
                            $columns[$col_name]['bg_color'] = $array_styles[10];
                            break;

                        case strstr($col_name, 'Class weight'):
                            $columns[$col_name]['bg_color'] = $array_styles[11];
                            break;

                        case strstr($col_name, 'Class length'):
                        case strstr($col_name, 'Length'):
                        case strstr($col_name, 'Width'):
                        case strstr($col_name, 'Height'):
                        case strstr($col_name, 'Weight'):
                            $columns[$col_name]['bg_color'] = $array_styles[12];
                            break;

                        case strstr($col_name, 'Attr. Group'):
                        case strstr($col_name, 'Attribute'):
                        case strstr($col_name, 'Attribute value'):
                            $columns[$col_name]['bg_color'] = $array_styles[13];
                            break;

                        case strstr($col_name, 'Filter Group'):
                        case strstr($col_name, 'Filter Gr.'):
                            $columns[$col_name]['bg_color'] = $array_styles[14];
                            break;
                        case strpos($col_name, 'Comb.') !== false:
                            $columns[$col_name]['bg_color'] = $array_styles[15];
                        break;

                        default:
                            $columns[$col_name]['bg_color'] = $array_styles[0];
                            break;
                    }
                }
            }
            return $columns;
        }

        function check_cell_limit($elements) {
            foreach ($elements as $key => $fields) {
                foreach ($fields as $field_name => $value) {
                    if(strlen($value) > 32767) {
                        $message = sprintf($this->language->get('xlsx_error_max_character_by_cell_2'), $field_name, substr(strip_tags($value), 0, 200) . '...');
                        if($this->main_field != '' && array_key_exists($this->main_field, $this->columns_fields) && array_key_exists($this->columns_fields[$this->main_field], $fields)) {
                            $message .= sprintf($this->language->get('xlsx_error_max_character_by_cell_3'), $this->main_field, $fields[$this->columns_fields[$this->main_field]]);
                        }

                        $this->exception($message);
                    }
                }
            }
        }
    }
?>
