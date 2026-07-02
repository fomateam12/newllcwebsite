<?php
    require_once DIR_SYSTEM . 'library/google_spreadsheets_2/apiclient/vendor/autoload.php';
    
    use Google\Client;
    use Google\Service\Sheets;
    use Google\Service\Sheets\BatchUpdateSpreadsheetRequest;
    use Google\Service\Sheets\Request;
    use Google\Service\Sheets\ValueRange;

    class ModelExtensionModuleIeProFileSpreadsheet extends ModelExtensionModuleIeProFile {
        public function __construct($registry){
            parent::__construct($registry);

            if (version_compare(phpversion(), '7', '<')) {
                $this->exception( sprintf($this->language->get('profile_import_export_php_version_too_old'), phpversion()));
            }
        }

        function create_file() {
            $GoogleAccessToken = false;

            if(file_exists($this->google_spreadsheet_json_file_path)) {
                putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->google_spreadsheet_json_file_path);
                $client = new Client();
                $client->useApplicationDefaultCredentials();

                $client->setApplicationName("Opencart - Export/Import PRO");
                $client->setScopes(['https://www.googleapis.com/auth/drive', 'https://www.googleapis.com/auth/spreadsheets']);

                if ($client->isAccessTokenExpired()) {
                    $client->useApplicationDefaultCredentials();
                }

                $GoogleAccessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
                $this->client = $client;
            }

            $this->GoogleAccessToken = $GoogleAccessToken;
            
            if(!$GoogleAccessToken) {
                $this->exception($this->language->get('google_spreadsheet_error_token'));
            }

            $filename = $this->profile['import_xls_spreadsheet_name'];

            if(empty($filename))
                $this->exception($this->language->get('google_spreadsheet_error_empty_filename'));

            $this->google_sheets_id = $filename;

            $this->sheets_service = new Sheets($this->client);

            try {
                $worksheetSheets = $this->sheets_service->spreadsheets->get($this->google_sheets_id);
            } catch (Exception $e) {
                $error = json_decode($e->getMessage(), true);

                $error_tring = '';

                if(!empty($error['error']['code']) && !empty($error['error']['message']))
                    $this->exception("<b>Error code:</b> ".$error['error']['code'].'<br><b>Message:</b> '.$error['error']['message'].'<br><b>Status:</b> '.(!empty($error['error']['status']) ? $error['error']['status'] : ''));
                else
                    $this->exception($e->getMessage());
            }

            $this->filename = $worksheetSheets->getProperties()->getTitle();
        }

        function insert_columns($columns) {}

        function insert_data($columns, $elements) {
            $sheet_name = $this->language->get('xlsx_sheet_name_'.$this->profile['import_xls_i_want']).'-'.date('Y-m-d-His');

            foreach( $columns as $key2 => $col ) {
                $final_column_names[] = $col['custom_name'];
            }

            $final_elements = array();
            foreach( $elements as $element_id => $element ) {
                $temp = array();

                foreach( $columns as $col_name => $col_info ) {
                    $custom_name = $col_info['custom_name'];
                    $temp[] = array_key_exists($custom_name, $element) ? str_replace(array("\r", "\n", '/\s+/g', '/\t+/'), '', $element[$custom_name]) : '';
                }

                $final_elements[] = $temp;
            }

            $message = $this->language->get('google_spreadsheet_sending_data');
            $this->update_process($message);


            // Create a new sheet
            $requests = [
                new Request([
                    'addSheet' => [
                        'properties' => [
                            'title' => $sheet_name,
                            'gridProperties' => [
                                'rowCount' => (count($final_column_names) + count($final_elements)),
                                'columnCount' => count($final_column_names)
                            ]
                        ],
                    ]
                ]),
            ];

            $createSheetRequestBody = new BatchUpdateSpreadsheetRequest([
                'requests' => $requests 
            ]);
            
            $createSheetResponse = $this->sheets_service->spreadsheets->batchUpdate($this->google_sheets_id, $createSheetRequestBody);
            $sheetId = $createSheetResponse->getReplies()[0]->getAddSheet()->getProperties()->getSheetId();
            $sheetName = $createSheetResponse->getReplies()[0]->getAddSheet()->getProperties()->getTitle();

            // Update sheet with data
            $range = "{$sheetName}";
            $addDataParams = ['valueInputOption' => 'USER_ENTERED'];

            $values = [];
            $values[] = $final_column_names;

            foreach( $final_elements as $number_row => $element ) {
                foreach( $element as $number_column => $data ) {
                    if( is_numeric($data) && strlen(substr(strrchr($data, "."), 1)) > 2 ) {
                        $data = number_format($data, 2);
                    } elseif( in_array($data, array('+', '-', '*', '=', '%')) ) {
                        $data = '~' . $data;
                    }

                    $final_elements[$number_row][$number_column] = $data;
                }

                $values[] = $final_elements[$number_row];
            }

            $addDataBody = new ValueRange([
                'majorDimension' => 'ROWS',
                'values' => $values
            ]);
            
            $this->sheets_service->spreadsheets_values->update($this->google_sheets_id, $range, $addDataBody, $addDataParams);

            // Deleted old sheets
            $deleteSheetsRequests = [];
            $getSpreadsheetResponse = $this->sheets_service->spreadsheets->get($this->google_sheets_id);
            
            foreach( $getSpreadsheetResponse->getSheets() as $sheet ) {
                $_sheetId = $sheet->getProperties()->getSheetId();

                if( $_sheetId != $sheetId ) {
                    $deleteSheetsRequests[] = new Request([
                        'deleteSheet' => [
                            'sheetId' => $_sheetId,
                        ]
                    ]);
                }
            }

            if( !empty($deleteSheetsRequests) ) {
                $deleteSheetsRequestsBody = new BatchUpdateSpreadsheetRequest([
                    'requests' => $deleteSheetsRequests
                ]);

                $this->sheets_service->spreadsheets->batchUpdate($this->google_sheets_id, $deleteSheetsRequestsBody);
            }
        }

        public function get_data() {
            $this->create_file();

            $finalXlsData = array('columns' => array(), 'data' => array());

            
            // Get the file by ID
            $getSpreadsheetResponse = $this->sheets_service->spreadsheets->get($this->google_sheets_id);
            $sheets = $getSpreadsheetResponse->getSheets();
            
            if( count($sheets) > 0 ) {
                $sheet = $sheets[0];
                $title = $sheet->getProperties()->getTitle();

                $valueRangeResponse = $this->sheets_service->spreadsheets_values->get($this->google_sheets_id, $title);
                $valueRanges = $valueRangeResponse->getValues();

                foreach( $valueRanges as $rowIndex => $columns ) {
                    // Put columns
                    if( $rowIndex == 0 ) {
                        $finalXlsData['columns'] = $columns;
                    } else {
                        $values = array_values($columns);
                        
                        foreach( $values as $key => $value ) {
                            if( !empty($value) && is_string($value) && $value[0] == '~' ) {
                                $values[$key] = substr($value, 1);
                            } elseif( is_numeric(str_replace([',', '.'], '', $value)) ) {
                                $values[$key] = str_replace(',', '', $value);
                            }
                        }

                        $finalXlsData['data'][] = $values;
                    }
                }
            }

            return $finalXlsData;
        }

        public function format_column_name($name) {
            $name = strtolower($name);
            $name = str_replace(array('*', ' ', '(', ')', '_'), '', $name);
            return $name;
        }
    }
?>