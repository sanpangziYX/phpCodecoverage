<?php

require_once '/home/www/phpCodeCoverage/src/Woojean/PHPCoverage/Reporter.php';
$reporter = new Woojean\PHPCoverage\Reporter('/opt/covlog','/home/www/phpCodeCoverage/demo/example.ignore',true);
$reporter->report();
