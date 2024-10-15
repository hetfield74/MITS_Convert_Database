<?php
/**
 * --------------------------------------------------------------
 * File: mits_convert_database.php
 * Date: 14.10.2024
 * Time: 10:45
 *
 * Author: Hetfield
 * Copyright: (c) 2024 - MerZ IT-SerVice
 * Web: https://www.merz-it-service.de
 * Contact: info@merz-it-service.de
 * --------------------------------------------------------------
 */

include('includes/application_top.php');
set_time_limit(0);

$characterset_array = array(
  array('id' => 'utf8mb4_unicode_ci', 'text' => 'utf8mb4_unicode_ci (empfohlen)'),
  array('id' => 'utf8mb4_general_ci', 'text' => 'utf8mb4_general_ci (empfohlen)'),
  array('id' => 'utf8mb4_german2_ci', 'text' => 'utf8mb4_german2_ci'),
  array('id' => 'utf8_general_ci', 'text' => 'utf8_general_ci'),
  array('id' => 'utf8_unicode_ci', 'text' => 'utf8_unicode_ci'),
  array('id' => 'utf8_german2_ci', 'text' => 'utf8_german2_ci'),
  array('id' => 'latin1_general_ci', 'text' => 'latin1_general_ci'),
  array('id' => 'latin1_german1_ci', 'text' => 'latin1_german1_ci'),
  array('id' => 'latin1_german2_ci', 'text' => 'latin1_german2_ci'),
);

$engine_array = array(
  array('id' => 'InnoDB', 'text' => 'InnoDB (empfohlen, wird bei allen utf8mb4 Zeichens&auml;tzen automatisch gesetzt)'),
  array('id' => 'MyISAM', 'text' => 'MyISAM'),
);

$db_engine = isset($_POST['engine']) ? xtc_db_input($_POST['engine']) : (defined('DB_SERVER_ENGINE') ? DB_SERVER_ENGINE : 'InnoDB');

if (isset($_POST['collation'])) {
    $db_collation = xtc_db_input($_POST['collation']);
    switch ($db_collation) {
        case 'utf8mb4_unicode_ci':
        case 'utf8mb4_general_ci':
        case 'utf8mb4_german2_ci':
            $db_characterset = 'utf8mb4';
            $db_engine = 'InnoDB';
            break;
        case 'utf8_unicode_ci':
        case 'utf8_general_ci':
        case 'utf8_german2_ci':
            $db_characterset = 'utf8';
            break;
        case 'latin1_general_ci':
        case 'latin1_german1_ci':
        case 'latin1_german2_ci':
            $db_characterset = 'latin1';
            break;
    }
} else {
    $db_characterset = defined('DB_SERVER_CHARSET') ? DB_SERVER_CHARSET : 'utf8mb4';
    $db_collation = $db_characterset . '_general_ci';
}

$action = $_POST['action'] ?? '';

$output_first = '
<!doctype html>
<html>
<head>
<meta charset="' . (isset($_SESSION['language_charset']) ? $_SESSION['language_charset'] : 'utf-8') . '">
<title>MITS Datenbankkonvertierung</title>
<style>
  body{padding:20px;}
  table{width:100%;border-collapse:collapse;border:1px solid black;background:#fff;}
  th{background:#333;color:#fff;font-weight:bold}  
  table td,td,th{padding:0.2em;text-align:left;border:1px solid black;}
  td.center,th.center{text-align:center}
  td.right,th.right{text-align:right}
  .table-scrollable{width:100%;overflow-y:auto;margin:0 0 1em;}
  .table-scrollable::-webkit-scrollbar{-webkit-appearance:none;width:14px;height:14px;}
  .table-scrollable::-webkit-scrollbar-thumb{border-radius:8px;border:3px solid #fff;background-color:rgba(0, 0, 0, .3);}
  .red{color:#900;}
  .green{color:#0A0;}
  .black{color:#222;}
  input[type=submit],a.button{cursor:pointer;border:0;font-size:16px;display:block;padding:6px;margin:20px auto;color:#fff;background:#555;width:60%;-webkit-border-radius:4px;-moz-border-radius:4px;border-radius:4px;text-decoration:none;}
  input[type=submit]:hover,a.button:hover{color:#fff;background:#222;}
  .messages{border:1px solid #0A0;margin:10px auto;padding:8px 8px 28px 8px;text-align:center;color:#0A0;font-weight:bold;background:#fff;}
  .error_messages{border:1px solid #900;margin:10px auto;padding:8px 8px 28px 8px;text-align:center;color:#900;font-weight:bold;background:#fff;}
  ul{margin:0;padding:0;list-style-type:none;}
  li{list-style-type:none;padding:5px;}
  .options{display:flex;justify-content:center;align-items:center;}
  .option{width:50%;flex: 0 1 auto;}
  label{width:20%;}
  select{width:75%;}
</style>
</head>
<body style="text-align:center;background:#ffe;font-family: Arial, Helvetica, sans-serif">
  <div style="text-align:center">
    <a href="https://www.merz-it-service.de/" title="Gehe zur Homepage von MerZ IT-SerVice">
      <img src="https://www.merz-it-service.de/images/logo.png" border="0" alt="MerZ IT-SerVice" title="Logo von MerZ IT-SerVice" style="margin:0 auto;display:inline-block;max-width:100%;height:auto;" />
    </a>
  </div>
  <h1 style="padding:6px;color:#444;font-size:18px;">MITS Datenbankkonvertierung v1.0</h1>';

$output = "";
$message = '';

$output = xtc_draw_form('convert_database', xtc_href_link(basename($PHP_SELF), xtc_get_all_get_params(), 'SSL')).xtc_draw_hidden_field('action', 'convert');
if ($action == '') {
    $output .= '<p>
Zur Konvertierung der Datenbank <i>' . DB_DATABASE . '</i> w&auml;hlen sie bitte die gew&uuml;nschte Collation und die gew&uuml;nschte Engine aus. 
Entsprechend der aktuellen Angaben in der configure.php (<i>includes/configure.php</i> und/oder <i>includes/local/configure.php</i>) sind die Auswahlm&ouml;glichkeiten bereits vorbelegt. 
W&uuml;nschen Sie abweichende &Auml;nderungen an der Datenbank, dann denken sie unbedingt daran, nach erfolgter Konvertierung die configure.php anzupassen!
</p>';
}
$output .= '<div class="options">';
$output .= '<div class="option"><label for="collation">Collation:</label> ' . xtc_draw_pull_down_menu('collation', $characterset_array, $db_collation, 'id="collation"') . '</div>';
$output .= '<div class="option"><label for="engine">Engine:</label> ' . xtc_draw_pull_down_menu('engine', $engine_array, $db_engine, 'id="engine"') . '</div>';
$output .= '</div>';
$sql = "SHOW TABLES";
$result = xtc_db_query("SHOW TABLES");
if (xtc_db_num_rows($result) > 0) {
    $output .= "<input type=\"submit\" class=\"button\" value=\"Datenbankkonvertierung starten &raquo;\">";
    $output .= "
  <table class=\"table-scrollable\">
    <thead>
      <tr>
        <th>Datenbanktabelle</th>
        <th class=\"center\">Start-Collation</th>
        <th class=\"center\">Start-Engine</th>
";
    if ($action == 'convert') {
        $output .= "
        <th class=\"center\">Ziel-Collation</th>
        <th class=\"center\">Collation-Ergebnis</th>
        <th class=\"center\">Ziel-Engine</th>
        <th class=\"center\">Engine-Ergebnis</th>
";
    }
    $output .= "
      </tr>
    </thead>
    <tbody>
";

    while ($row = xtc_db_fetch_array($result)) {
        $table_name = $row['Tables_in_' . DB_DATABASE];

        $check_engine = xtc_db_query("SHOW TABLE STATUS WHERE Name = '" . $table_name . "'");
        $engine = xtc_db_fetch_array($check_engine);
        $table_engine = $engine['Engine'] ?? '';
        $table_collation = $engine['Collation'] ?? '';

        $output .= "<tr><td><strong>" . $table_name . "</strong></td><td class=\"center\">" . $table_collation . "</td><td class=\"center\">" . $table_engine . "</td>";

        if ($action == 'convert' && !empty($table_name) && !empty($table_engine) && !empty($table_collation)) {
            $message = '<div class="messages"><p class="success_message">Die Konvertierung wurde mit folgendem Ergebnis durchgef&uuml;hrt:</p><ul class="message_list">';

            if ($table_collation != $db_collation) {
                if (xtc_db_query("ALTER TABLE `" . $table_name . "` CONVERT TO CHARACTER SET " . $db_characterset . " COLLATE " . $db_collation)) {
                    $collation_changed = true;
                    $output .= "<td class=\"center green\">" . $db_collation . "</td><td class=\"center green\">konvertiert</td>";
                } else {
                    $collation_changed = false;
                    $output .= "<td class=\"center red\">" . $db_collation . "</td><td class=\"center red\">fehlgeschlagen</td>";
                }
            } else {
                $output .= "<td class=\"center black\">" . $table_collation . "</td><td class=\"center black\">keine &Auml;nderung</td>";
            }

            if ($table_engine != $db_engine) {
                if (xtc_db_query("ALTER TABLE `" . $table_name . "` ENGINE = " . $db_engine)) {
                    $engine_changed = true;
                    $output .= "<td class=\"center green\">" . $db_engine . "</td><td class=\"center green\">konvertiert</td>";
                } else {
                    $engine_changed = false;
                    $output .= "<td class=\"center red\">" . $db_engine . "</td><td class=\"center red\">fehlgeschlagen</td>";
                }
            } else {
                $output .= "<td class=\"center black\">" . $table_engine . "</td><td class=\"center black\">keine &Auml;nderung</td>";
            }

        }

        $output .= "</tr>";
    }

    if ($action == 'convert') {
        if (xtc_db_query("ALTER DATABASE `" . DB_DATABASE . "` CHARACTER SET " . $db_characterset . " COLLATE " . $db_collation)) {
            $message .= '<li class="green">Die Collation wurde auf ' . $db_collation . ' gesetzt und der Zeichensatz wurde auf ' . $db_characterset . ' ge&auml;ndert!</li>';
        } else {
            $message .= '<li class="red">Die Collation und der Zeichensatz konnten nicht ge&auml;ndert werden!</li>';
        }

        if (xtc_db_query("SET GLOBAL default_storage_engine = " . $db_engine)) {
            $message .= '<li class="green">Die Engine der Datenbank wurde auf ' . $db_engine . ' ge&auml;ndert!</li>';
        } else {
            $message .= '<li class="red">Die Engine der Datenbank konnte nicht auf ' . $db_engine . ' ge&auml;ndert werden!</li>';
        }

        $charset_notice = defined('DB_SERVER_CHARSET') && DB_SERVER_CHARSET != $db_characterset ? '<span class="red">Der Zeichensatz bei DB_SERVER_CHARSET muss in der includes/configure.php angepasst werden auf ' . $db_characterset . '!</span>' : 'Der Zeichensatz stimmt mit der Angabe in der includes/configure.php &uuml;berein!';
        $engine_notice =  defined('DB_SERVER_ENGINE') && DB_SERVER_ENGINE != $db_engine ? '<span class="red">Der Datenbankengine bei DB_SERVER_ENGINE muss in der includes/configure.php angepasst werden auf ' . $db_engine . '!</span>' : 'Der Datenbankengine stimmt mit der Angabe in der includes/configure.php &uuml;berein!';
        $message .= '</ul><p>Bitte beachten sie unbedingt folgende Hinweise:<br>' . $charset_notice . '<br>' . $engine_notice .'</p></div>';
    }

    $output .= "</tbody></table>";
    $output .= "<input type=\"submit\" class=\"button\" value=\"Datenbankkonvertierung starten &raquo;\">";
} else {
    $output .= "<p class='error_message'>Keine Tabellen gefunden!</p>";
}
$output .= "</form>
      <a class=\"button\" href=\"" . basename($PHP_SELF) . "\">Zur&uuml;ck zum Modul &raquo;</a>
      <p style=\"text-align:center;padding:6px;color:#555;font-size:11px;margin-top:50px;\"> 
        &copy; by <a href=\"https://www.merz-it-service.de/\">
        <span style=\"padding:2px;background:#ffe;color:#6a9;font-weight:bold;\">Hetfield (MerZ IT-SerVice)</span>
        </a>
      </p>";

echo $output_first . $message . $output;
?>
</body></html>