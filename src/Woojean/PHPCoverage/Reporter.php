<?php
namespace Woojean\PHPCoverage;

class Reporter{
	private $logDir = '';
	private $ignoreFile = '';

	function __construct($logDir,$ignoreFile){
		$this->logDir = $logDir;
		$this->ignoreFile = $ignoreFile;
	}

	protected function isIgnore($srcPath){
		$ignores = require($this->ignoreFile);

		foreach ($ignores as $key => $value) {			
			$count = substr_count($srcPath,$value);
			if($count > 0){
				return true;
			}
		}
		return false;
	}
	

	public function report(){
		$allCoverageData = $this->mergeCoverages();
		
		$html = $this->TEMPLATE_REPORT;
		$items = '';

		$sumFiles = 0;
		$sumLines = 0;
		$sumExcutable = 0;
		$sumCovered = 0;
		$sumCoverRate = 0;

		foreach ($allCoverageData as $file => $lines) {
			if($this->isIgnore($file)){
				continue;
			}



			$fileItem = $this->TEMPLATE_NAV_ITEM;
			$reportPath = str_replace('/','-',$file);
			$reportPath = str_replace('.php','.html',$reportPath);

			$ret = $this->parseSrcFile($reportPath,$file,$lines);

			$fileStyle = '';
			$coverageRate = floatval($ret['coverage_rate']);
			if($coverageRate <= 0.2){
				$fileStyle = 'coverage_5';
			}
			else if( 0.2 < $coverageRate && $coverageRate <= 0.4){
				$fileStyle = 'coverage_4';
			}
			else if( 0.2 < $coverageRate && $coverageRate <= 0.4){
				$fileStyle = 'coverage_3';
			}
			else if( 0.2 < $coverageRate && $coverageRate <= 0.4){
				$fileStyle = 'coverage_2';
			}
			else{
				$fileStyle = 'coverage_1';
			}

			
			$fileCoverage = strval($coverageRate*100).'% ('.$ret['lines_covered'].'/'.$ret['lines_excutable'].')';

			$fileItem = str_replace('%FILE_PATH%',$reportPath,$fileItem);
			$fileItem = str_replace('%FILE_NAME%',$file,$fileItem);
			$fileItem = str_replace('%FILE_STYLE%',$fileStyle,$fileItem);
			$fileItem = str_replace('%FILE_COVERAGE%',$fileCoverage,$fileItem);

			$items .= $fileItem;

			$sumFiles += 1;
			$sumLines += intval($ret['lines_all']);
			$sumExcutable += intval($ret['lines_excutable']);
			$sumCovered += intval($ret['lines_covered']);
		}
		$sumCoverRate = strval(round(($sumCovered/$sumExcutable),4)*100).'%';

		$html = str_replace('%FILE_ITEMS%', $items, $html);


        $html = str_replace('%SUM_FILES%', $sumFiles, $html);
        $html = str_replace('%SUM_LINES%', $sumLines, $html);
        $html = str_replace('%SUM_EXCUTABLE%', $sumExcutable, $html);
        $html = str_replace('%SUM_COVERED%', $sumCovered, $html);
        $html = str_replace('%SUM_COVERRATE%', $sumCoverRate, $html);

		file_put_contents($this->logDir.DIRECTORY_SEPARATOR.'index.html', $html);
	}

	protected function parseSrcFile($reportPath,$srcPath,$lines){
		
		$result = [
			'lines_all' => 0,
			'lines_excutable' => 0,
			'lines_covered' => 0,
			'coverage_rate' => 0
		];

		$coverIndex = array_keys($lines);
		$src = file_get_contents($srcPath);
		$arr = explode(PHP_EOL, $src);

		$html = $this->TEMPLATE_FILR_REPORT;
		$allLines = count($arr)-2;
		$excutableLines = 0;
		$coverLines =count($coverIndex);

		$str = '';
		foreach ($arr as $key => $value) {
			if($key<1){
				continue;
			}
			$code = preg_replace('/\s+/','&nbsp;',$value);

			if(!$this->is_line_excutable($value)){
				if(in_array($key+1, $coverIndex)){
					$coverLines -= 1; // !!!
				}
				$str .= '<tr class="e"><td class="line_num">'.$key.'</td><td >'.$code.'</td></tr>';
			}
			elseif(in_array($key+1, $coverIndex)){
				$str .= '<tr class="c"><td class="line_num">'.$key.'</td><td>'.$code.'</td></tr>';
				$excutableLines +=1; 
			}
			else{
				$str .= '<tr class="u"><td class="line_num">'.$key.'</td><td >'.$code.'</td></tr>';
				$excutableLines +=1;
			}

		}
		$str = '<table>'.$str.'</table>';
		$html = str_replace('$TABLE$', $str, $html);
		foreach ($this->keywords as $key => $value) {
			$html=str_replace($value.'&nbsp;','<label class="k">'.$value.'&nbsp;</label>', $html);
		}

		$reportPath = $this->logDir.DIRECTORY_SEPARATOR.$reportPath;

		file_put_contents($reportPath, $html);


		$result['lines_all'] = $allLines;
		$result['lines_excutable'] = $excutableLines;
		$result['lines_covered'] = $coverLines;
		if(intval($excutableLines)>0){
			$result['coverage_rate'] = round(floatval($coverLines)/floatval($excutableLines) ,2);
		}
		else{
			$result['coverage_rate'] = '0';
		}
		
		return $result;
	}


	private $docFlag = false;
	public function is_line_excutable($line){
		if($this->docFlag && empty(strstr($line,'*/'))){
			return false;
		}

		if( !empty(strstr($line,'/*')) ){
			$this->docFlag = true;
			return false;
		}
		if( !empty(strstr($line,'*/')) ){
			$this->docFlag = false;
			return false;
		}
		if( !empty(strstr($line,'//')) ){
			return false;
		}
		if(strlen(trim($line))<2){
			return false;
		}
		return true;
	}

	protected function mergeCoverages(){
		$allCoverageData = [];
		$coverageFiles = $this->getCoverageFiles();
		foreach ($coverageFiles as $key => $value) {
			$file = $this->logDir.DIRECTORY_SEPARATOR.$value;
			$arr = json_decode(file_get_contents($file),true);
			foreach ($arr as $fileName => $coverageLines) {
				if(isset($allCoverageData[$fileName])){
					$allCoverageData[$fileName] = $allCoverageData[$fileName]+$coverageLines;
				}
				else{
					$allCoverageData[$fileName] = $coverageLines;
				}
			}
		}
		return $allCoverageData;
	}

	protected function getCoverageFiles(){
		$files = [];
		$dh = opendir($this->logDir);
  		while ($file=readdir($dh)) {
  			if('coverage' == pathinfo($file)['extension']){
  				$files[] = $file;
  			}
    	}
  		closedir($dh);
  		return $files;
	}



	// ====================== templates ===========================
	private $keywords = array('__halt_compiler', 'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch', 'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'eval', 'exit', 'extends', 'final', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'interface', 'isset', 'list', 'namespace', 'new', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once', 'return', 'static', 'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while', 'xor','const','CONST');

	private $TEMPLATE_NAV_ITEM = '<li><a href="%FILE_PATH%"><label>%FILE_NAME%</label><span class="%FILE_STYLE%">%FILE_COVERAGE%</span></a></li>';

	private $TEMPLATE_REPORT = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHPCoverage Code Coverage Report</title>
    <style>
			html,
			body,
			div,
			span,
			iframe {
				margin: 0;
				padding: 0;
			}
			
			html,
			body {
				height: 100%;
				font-size: 0.8em;
				font-family: "Microsoft YaHei" ! important;
			}
			
			ul {
				list-style-type: none;
				margin: 0;
				padding: 0;
				float: left;
				width: 100%;
			}
			
			li {
				list-style-type: none;
				height: 30px;
				line-height: 30px;
				background: #CCFFCC;
				font-size: 0.8em;
				text-align: left;
				border: 1px solid white;
				padding: 0;
			}
			
			.coverage_1 {
				background: #AAFFAA;
			}
			
			.coverage_2 {
				background: #B3EE3A;
			}
			
			.coverage_3 {
				background: #EEEE00;
			}
			
			.coverage_4 {
				background: #FFC125;
			}
			
			.coverage_5 {
				background: #F08080;
			}
			
			li label {
				width: 70%;
				float: left;
				height: 30px;
				cursor: pointer;
			}
			
			li span {
				width: 25%;
				height: 30px;
				float: right;
				text-align: right;
			}
			
			.select {
				border: 3px solid red;
			}
			
			.sum {
				position:fixed;
				top: 0;
				width: 100%;
				height: 150px;
				line-height: 30px;
				padding-left: 20px;
				border: 1px solid #eee;
				
			}
			
			.sum {
				font-size: 0.8em;
			}
			
			.sum label {}
			
			.sum span {
				color: red;
				margin-right: 50px;
			}
			
			.sum a {
				float: right;
				margin-right: 100px;
			}
			
			.filelist {
				position: fixed;
				left: 100px;
				width: 42%;
				top: 200px;
				bottom:35px;
				overflow: auto;
			}
			
			.con {
				width: 100%;
				height: 100%;
				float: right;
			}
			
			.container {
				position: fixed;
				left: 52%;
				width: 45%;
				top:200px;
				bottom:35px;
			}
						
			table {
				width: 80%;
				box-sizing: border-box;
				position: fixed;
				top: 100px;
				left: 100px;
				background: #D2F7FC;
			}
			tr td {
				text-align: center;
				
			}
			.summary{
				position: fixed;
				top: 70px;
				font-size: 20px;
				left: 100px;
			}
			.title{
				position: fixed;
				font-size: 45px;
				top: 30px;
				left: 700px;
			}
			.detail{
				position: fixed;
				top: 160px;
				font-size: 20px;
				left: 100px;
			}
			.footer{
				position: fixed;
				height: 35px;
				bottom: 0px;
				width: 100%;
				background-color: white;
				font-size:10px;
				text-align:center;
				line-height: 35px;
			}
		</style>

</head>
<body>
    <div class="sum">
			<div class="title">
				PHP代码覆盖率报告
			</div>
			<div class="summary">
				summary
			</div>

       <table  border="1px" bordercolor="#AAFFAA">
				<tr>
					<td>执行总文件数：</td>
					<td>%SUM_FILES%</td>
					<td>代码总行数：</td>
					<td>%SUM_LINES%</td>
					<td>可执行代码行数：</td>
					<td>%SUM_EXCUTABLE%</td>
					<td>覆盖可执行代码行数：</td>
					<td>%SUM_COVERED%</td>
					<td>可执行代码覆盖率：</td>
					<td>%SUM_COVERRATE%</td>
				</tr>
		
			</table>
		<div class="detail">
				detail
		</div>

    </div>
    <div class="navgation" id="navgation">
        <div class="filelist">
		<ul>
			%FILE_ITEMS%
		</ul>
        </div>
    </div>
	<div class="container">
		<iframe class="con" src="" frameborder="0" id="content"></iframe>
	</div>
	<div class="footer">系统研发中心--测试部--测试开发组</div>
</body>
<script>
    var btns = document.getElementById("navgation").getElementsByTagName("a");
    var tabCon = document.getElementById("content");
    for (var i = 0; i < btns.length; i++) {
        btns[i].onclick = function(){
            for (var i = 0; i < btns.length; i++) {
                btns[i].className = "";
            };
            this.className = "select";
            tabCon.src = this.href;
            return false;
        }
    };
</script>
</html>';

	private $TEMPLATE_FILR_REPORT = '
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
html,body,div,span,iframe{
	margin: 0;
	padding: 0;
}

html,body{
	height: 100%;
	font-family: "Microsoft YaHei" ! important;
}

.e{
	background-color:#F0F0F0;
}

.c{
	background-color:#B4EEB4;
}

.u{
	background-color:#FFFAF0;
}

.k{
	font-weight:bold;
	color:blue;
}

.line_num{
	font-size:0.8em;
}

table {
	font-size:0.8em;
}

</style>
</head>
<body>
	$TABLE$
</body>
</html>
';
}