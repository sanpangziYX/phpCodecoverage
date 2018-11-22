

<?php
//phpinfo();
require_once '/home/www/phpCodeCoverage/src/Woojean/PHPCoverage/Injecter.php';
Woojean\PHPCoverage\Injecter::Inject([
	'log_dir'=>'/opt/covlog',
//	'ignore_file'=>'/home/savior/PHPCoverage/demo/example.ignore',
	'is_repeat' => true 
]);

require_once '/opt/zentaopms/www/index_old.php';







