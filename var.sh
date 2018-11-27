#!/bin/bash
#git_dir=infra.xesv5.com
git_dir=$1
echo $git_dir

#job_name=应用-发布平台
job_name=$2
# if [ -d /home/www/phpCodeCoverage ];then
# echo "git目录存在，pull"
# git pull ssh://git@git.xesv5.com:10088/yanxin3/phpCodeCoverage.git
# else
# cd /home/www
# git clone ssh://git@git.xesv5.com:10088/yanxin3/phpCodeCoverage.git
# fi
#------执行remote shell-----------------------------------------
#-----拼接项目字符串---
depart=${job_name%%-*}
echo $depart
project=${job_name##*-}
echo $project
env=测试
echo $env
#找到index.php所在目录
FILE_PATH=`find /home/www/$git_dir -name "index.php" -exec dirname {} \;`
echo $FILE_PATH
cd $FILE_PATH
file_name=$FILE_PATH/index_old.php
echo $file_name
#替换文件
covlog=/home/www/report/$git_dir
echo $covlog
mkdir -m 777 -p $covlog
echo "创建文件夹成功"
#复制替换gatherReport的收集路径
cp /home/www/phpCodeCoverage/src/Woojean/PHPCoverage/gatherReport.php $covlog
sed -i 's#/opt/covlog#'$covlog'#g' $covlog/gatherReport.php

if [ -f $file_name ];then
echo "$file_name存在"
cd $FILE_PATH
#复制index.php至项目路径下
\cp /home/www/phpCodeCoverage/src/Woojean/PHPCoverage/index.php $FILE_PATH/
#将文件index中的项目路径替换
sed -i 's#/opt/zentaopms/www#'$FILE_PATH'#g'  $FILE_PATH/index.php
#替换index的covlog路径
sed -i 's#/opt/covlog#'$covlog'#g'  $FILE_PATH/index.php
else
echo "$file_name不存在"
cd $FILE_PATH
mv index.php index_old.php
\cp /home/www/phpCodeCoverage/src/Woojean/PHPCoverage/index.php $FILE_PATH/
#将文件index中的项目路径替换
sed -i 's#/opt/zentaopms/www#'$FILE_PATH'#g'  $FILE_PATH/index.php
#替换index的covlog路径
sed -i 's#/opt/covlog#'$covlog'#g'  $FILE_PATH/index.php
fi
#if [ $start = 'start' ];then
	echo "--执行脚本分析结果shell----"
	#进入到脚本目录，执行脚本分析结果
	time=`date	"+%Y-%m-%d-%H%M"`
	cd $covlog
	new_covlog=/home/www/phpCodeCoverage/report/$depart/$project/$env/$time
	echo $new_covlog
	mkdir -p $new_covlog && chmod 777 $new_covlog
	php gatherReport.php
	sed -i 's#PHP代码覆盖率报告#'$job_name'代码覆盖率报告#g' $covlog/index.html
	cp -r $covlog/*.html $new_covlog
	# if [ $restart = 'true' ];then
	# echo "重新计数覆盖率"
	# rm -rf $covlog/*
	# fi	
	
scp -r /home/www/phpCodeCoverage/report/ root@10.17.81.62:/usr/local/tomcat8.0.53/webapps/ROOT/
rm -rf /home/www/phpCodeCoverage/report
#钉钉发送最新报告
curl -H "Content-Type:application/json;charset=utf-8" -X POST -d "{\"msgtype\":\"link\",\"link\": {\"text\":\"PHP代码覆盖率\", \"title\": \"项目名称：$job_name\n日期：$time\", \"picUrl\": \"\",\"messageUrl\": \"http://10.17.81.62:8080/report/$depart/$project/$env/$time\"}}"  https://oapi.dingtalk.com/robot/send?access_token=8d1904f2ca84d7f6c18251b
#fi
