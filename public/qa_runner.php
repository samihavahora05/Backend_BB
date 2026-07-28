<?php
ini_set('max_execution_time', 300);
$output = shell_exec('node ../qa_tester.cjs 2>&1');
echo "<pre>$output</pre>";
if (file_exists('../qa_report.json')) {
    echo "\n\n<h3>Report Output:</h3>\n";
    echo "<pre>" . htmlspecialchars(file_get_contents('../qa_report.json')) . "</pre>";
}
