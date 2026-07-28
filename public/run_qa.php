<?php
pclose(popen("start /B node ../qa_tester.cjs > ../qa_node.log 2>&1", "r"));
echo "QA Tester started in background. Check qa_report.json and qa_node.log later.";

