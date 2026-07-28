<?php
$files = [
    'd:\\blueboxx\\blueboxx web\\pages\\onboarding\\index.tsx',
    'd:\\blueboxx\\blueboxx web\\pages\\admin\\backup\\index.tsx',
    'd:\\blueboxx\\blueboxx web\\pages\\admin\\partners\\index.tsx',
    'd:\\blueboxx\\blueboxx web\\pages\\college\\students\\index.tsx'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        unlink($file);
        echo "Deleted: $file\n";
    } else {
        echo "Not found: $file\n";
    }
}
?>
