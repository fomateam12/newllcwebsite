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

    class ModelExtensionModuleIeProFileOds extends ModelExtensionModuleIeProFile {
        public function __construct($registry){
            parent::__construct($registry);
        }
        function create_file() {
            $this->filename = $this->get_filename();
            $this->filename_path = $this->path_tmp.$this->filename;
            $this->writer = WriterFactory::create(Type::ODS);
            $this->writer->openToFile($this->filename_path);
        }
        function insert_columns($columns) {
            foreach ($columns as $key2 => $col) {
                $final_column_names[] = $col['custom_name'];
            }

            $this->writer->addRow($final_column_names);
        }

        function insert_data($columns, $elements) {
            $elements_to_insert = count($elements);
            $count = 0;
            $message = sprintf($this->language->get('progress_export_elements_inserted'), 0, $elements_to_insert);
            $this->update_process($message);
            foreach ($elements as $element_id => $element) {
                $temp = array();
                foreach ($columns as $col_name => $col_info) {
                    $custom_name = $col_info['custom_name'];
                    $temp[] = array_key_exists($custom_name, $element) ? str_replace(array("\r", "\n", '/\s+/g', '/\t+/'), '', $element[$custom_name]) : '';
                }
                $this->writer->addRow($temp);
                $count++;
                $message = sprintf($this->language->get('progress_export_elements_inserted'), $count, $elements_to_insert);
                $this->update_process($message, true);
            }
            $this->writer->close();
        }

        function insert_data_multisheet($data) {
            $first_sheet = true;

            foreach ($data as $sheet_name => $sheet_data) {
                if($first_sheet) {
                    $this->writer->getCurrentSheet();
                    $first_sheet = false;
                } else
                    $this->writer->addNewSheetAndMakeItCurrent();

                $currentSheet = $this->writer->getCurrentSheet();
                $currentSheet->setName($sheet_name);

                //<editor-fold desc="Insert columns">
                    $this->writer->addRow($sheet_data['columns']);
                //</editor-fold>

                $message = sprintf($this->language->get('progress_export_inserting_sheet_data'), $sheet_name);
                $this->update_process($message);

                $this->writer->addRows($sheet_data['data']);
            }
            $this->writer->close();
        }

        function get_data() {
            $reader = ReaderFactory::create(Type::ODS);
            $reader->open($this->file_tmp_path);

            $final_excel = array(
                'columns' => array(),
                'data' => array(),
            );

            $rows = 0;

            $sheet_current = 1;
            $this->update_process(sprintf($this->language->get('progress_import_reading_rows'), $rows));

            foreach ($reader->getSheetIterator() as $sheet) {
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
            return $final_excel;
        }

        public function get_data_multisheet() {
            $reader = ReaderFactory::create(Type::ODS);
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
    }
?>