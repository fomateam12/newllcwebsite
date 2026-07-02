<?php
$_['cron_version'] = 'EXTENSION_VERSION';
$_['thead_cron_cron'] = 'Profile';
$_['thead_cron_status'] = 'Status';
$_['thead_cron_email'] = 'Report email';
$_['thead_cron_period'] = 'Repeat period';
$_['thead_cron_configurator'] = 'Quick tutorial';
$_['cron_all_minutes'] = 'All minutes';
$_['cron_all_hours'] = 'All hours';
$_['cron_all_days'] = 'All month days';
$_['cron_all_months'] = 'All months';
$_['cron_all_weekdays'] = 'All weekdays';
$_['cron_php_path'] = 'Path to php';
$_['cron_php_path_remodal_title'] = 'Path to php';
$_['cron_php_path_remodal_description'] = '        <p>Enter your PHP path here, if you are not sure, get in contact with your hosting company.</p>
        <p>Some common paths are:</p>
        <ul>
            <li>/usr/bin/php</li>
            <li>/usr/local/bin/php</li>
            <li>/usr/local/cpanel/3rdparty/bin/php</li>
        </ul>
    ';
$_['cron_php_path_remodal_link'] = '<b>IMPORTANT:</b> Click to read';
$_['cron_config_remodal_title'] = 'About CRON Jobs';
$_['cron_config_remodal_description'] = '<p>Here you can find 3 options for configure CRON Jobs <b>in your server settings</b>, we are not responsible for your actions carried out in the configuration of your server and the CRON Jobs settings <b>are not included in support</b>.</p>
<p>In Opencart side, for CRON Jobs can works, you have to <b>enable it</b> in this tab table (nothing more), also, if you enter an email address inside the input field "<b>Email</b>", a CRON job report will be sent to email address.</p>
<p>Do not forget click button "<b>Save CRONs configuration</b>" for save CRON Jobs settings.</p>
<br>
<h1>CRON Configuration - Server side</h1>
<p style="color: #0D4AA2;"><b>OPTION 1 - SETTING CRON JOBS WITH HOSTING INTERFACE:</b></p>
<p>Some modern hostings like "Plesk", "Cpanel"... has an interface to configure CRON Jobs, here you can see some example:</p>

<ol>
    <li>Select your webspace (option may not be available in your panel).</li>
    <li><b>Task type</b>: Run a PHP script</li>
    <li>Put CRON file path: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy path</a><div class="to_copy" style="display: none">CRON_PATH</div></a></li>
    <li>Put CRON arguments: <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy arguments</a><div class="to_copy" style="display: none">CRON_ARGUMENTS</div></a></li>
    <li>Set <b>Run</b> periods when you CRON will be executed.</li>
    <li>Set a <b>Description</b></li>
    <li>Click <b>OK button </b>. (or Save or Apply)</li>
</ol>
<img style="width: 605px;" src="%s">
<br><br>
<p style="color: #0D4AA2;"><b>OPTION 2 - INSERT CRON VIA SSH:</b></p>
<ol>
    <li>Access your Server <b>via SSH</b>. </li>
    <li>Execute command: <b>crontab –e</b></li>
    <li><b>Paste</b> CRON command (examples at bottom).</li>
    <li>Make desired changes and hit “<b>Ctr+X</b>” followed by “<b>Y</b>”.</li>
</ol>
<b><u>Example 1 - Every 15 minutes</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy example</a><div class="to_copy" style="display: none">*/15 * * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Example 2 - 1 time per day at 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy example</a><div class="to_copy" style="display: none">0 0 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Example 3 - 2 times per day at 00:00 and 12:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy example</a><div class="to_copy" style="display: none">0 */12 * * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Example 4 - Every Sunday at 00:00</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy example</a><div class="to_copy" style="display: none">0 0 * * 0 PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<b><u>Example 5 - Every Month</u></b> - <a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy example</a><div class="to_copy" style="display: none">0 0 30 * * PATH_TO_PHP CRON_PATH CRON_ARGUMENTS</div><br>
<br>
<b style="color: #f00;">PATH_TO_PHP:</b> In copied examples, you will see "PATH_TO_PHP", you have to resplace it by your server path to PHP, if you are not sure where you cant find it, put in contact with your hosting company.
<br><br>
<p style="color: #0D4AA2;"><b>OPTION 3 - WGET Command:</b></p>
<p>If you are having problems configuring CRON Jobs with the tradicional way, you can use "wget" command for execute your CRON Job</p>
<ol>
    <li>Make sure that your CRON Job is in mode "<b>Command</b>".</li>
    <li><a href="javascript:{}" onclick="copy_text_to_clipboard($(this).next(\'div.to_copy\').html())">Copy next command</a><div class="to_copy" style="display: none">CRON_WGET_COMMAND</div></a> and paste it in your "command" text area.</li>
    <li>Save your CRON Jobs settings.</li>
</ol>
<h1>Execute this CRON Job manually</h1>
You can execute a simulation of your CRON Job in <a href="EXECUTE_PROFILE_NOW" target="_blank">next link</a>. You will be unlogged of admin area.';

$_['cron_config_remodal_link'] = 'Quick tutorial';
$_['cron_error_profile_id'] = 'Error: Select a profile from CRONs table.';
$_['cron_error_path_to_php'] = 'Error: Close this popup and fill input \"<b>Path to php</b>\" required to your CRON Job command.';
$_['cron_command_copied'] = 'Copied to clipboard.';
$_['cron_month_1'] = 'January';
$_['cron_month_2'] = 'February';
$_['cron_month_3'] = 'March';
$_['cron_month_4'] = 'April';
$_['cron_month_5'] = 'May';
$_['cron_month_6'] = 'June';
$_['cron_month_7'] = 'July';
$_['cron_month_8'] = 'August';
$_['cron_month_9'] = 'September';
$_['cron_month_10'] = 'October';
$_['cron_month_11'] = 'November';
$_['cron_month_12'] = 'December';
$_['cron_weekday_0'] = 'Monday';
$_['cron_weekday_1'] = 'Tuesday';
$_['cron_weekday_2'] = 'Wednesday';
$_['cron_weekday_3'] = 'Thursday';
$_['cron_weekday_4'] = 'Friday';
$_['cron_weekday_5'] = 'Saturday';
$_['cron_weekday_6'] = 'Sunday';
$_['cron_save'] = 'Save CRONs configuration';
$_['cron_config_save_sucessfully'] = 'Configuration saved successfully!';
$_['cron_config_save_error_repeat_profiles'] = '<b>Error:</b> Duplicate profile found.';
$_['cron_error_disabled'] = 'Profile "<b>%s</b>" is disabled in CRON configuration.';
$_['cron_error_not_found'] = 'Profile "<b>%s</b>" not found in CRON configuration.';
?>