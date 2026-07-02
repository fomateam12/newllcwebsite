<?php
$_['migration_export_legend'] = '<b>EXPORT</b>: Export all store data. Use this option for backup or to be imported by another OpenCart store.';
$_['migration_export_button'] = 'Launch export process';
$_['migration_export_legend_destiny_label'] = 'Destination';
$_['migration_export_legend_destiny_none'] = 'This is a backup, not a migration.';
$_['migration_export_legend_destiny_oc1'] = 'Opencart 1.5.x';
$_['migration_export_legend_destiny_oc2'] = 'Opencart 2.x';
$_['migration_export_legend_destiny_oc3'] = 'Opencart 3.x';
$_['migration_export_legend_format_label'] = 'Format';
$_['migration_export_select_all_label'] = 'Select all categories';
$_['migration_export_error_select_category'] = '<b>Error:</b> Select at least a  category before export';
$_['migration_export_error_empty_data'] = '<b>Error:</b> No data to export';
$_['migration_export_profile_name'] = 'Profile Name';
$_['migration_export_save_profile'] = 'Save Migration Profile';
$_['migration_import_legend'] = '<b>IMPORT</b>: Import file from another Opencart store or restore a full backup.';
$_['migration_import_upload_file_button'] = 'Upload file';
$_['migration_import_warning_message_link'] = 'IMPORTANT: CLICK HERE BEFORE LAUNCHING THE IMPORT PROCESS';
$_['migration_import_warning_message_title'] = 'Important message';
$_['migration_import_warning_message_description'] = '    <p></p><b>BE CAREFUL</b>: during the migration process, all tables will be emptied before data is imported. Before you start that process, you should do a <b>full export</b> from this same tab and <b>full mysql database backup (.sql file)</b>.</p>

    <p>If you are migrating from 1.5.x to 2.x or 3.x it is possible you may get a language-related warning message at the end. Simply go to "<b>System > Localisation > Languages</b>", edit each language and press click "Save".</b></p>';
$_['migration_import_button'] = 'Launch import process';
$_['migration_import_error_xml_incompatible'] = '<b>Error:</b> XML file not compatible with import process, make sure that you did not change its structure.';
$_['migration_import_error_empty_file'] = '<b>Error:</b> Upload file before starting the import process.';
$_['migration_import_error_extension'] = '<b>Error:</b> file format not allowed, only "<b>%s</b>"';
$_['migration_import_processing_table'] = 'Procesing table "<b>%s</b>": Elements processed <b>%s</b> of <b>%s</b>';
$_['migration_import_empty_table'] = 'Data not found in table "<b>%s</b>"';
$_['migration_import_finished'] = '<b><i class="fa fa-check"></i>   Import finished successfully!</b>';
?>