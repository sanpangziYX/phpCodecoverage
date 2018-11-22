#!/bin/sh
#先进入WWW目录
cd /opt/zentaopms/
#再进入下一层目录
#cd *
project_path=$(cd `dirname $0`; pwd)
echo $project_path
project_name="${project_path##*/}"
echo $project_name

cd $project_path/www
file_name=$project_path/www/index_old.php
echo $file_name
time=`date	"+%Y-%m-%d-%M"`
covlog=/opt/covlog
echo $covlog
new_covlog=/opt/$project_name-$time
echo $new_covlog
if [ -f $file_name ]
then
echo "文件$file_name存在"
#进入到脚本目录
cd /opt/
php gatherReport.php
cp -r $covlog $new_covlog
rm -rf $covlog/*
scp -r $new_covlog/ root@10.17.91.33:/usr/local/tomcat8.0.53/webapps/ROOT/report/
rm -rf $new_covlog
else
echo "文件$file_name不存在"
cd $project_path/www
cp index.php index_old.php && rm index.php
mkdir -m 777 -p /opt/covlog
#mkdir -m 777 -p /tmp/git && cd /tmp/git
cd /opt
git clone ssh://git@git.xesv5.com:10088/yanxin3/phpCodeCoverage.git
cp /opt/phpCodeCoverage/src/Woojean/PHPCoverage/index.php $project_path/www/

#sed -i 's/\/opt\/zentaopms\/www\/index_old.php/$project_path/g'  index_old.php
sed -i 's/\/opt\/zentaopms/$project_path/g'  $project_path/www/index.php
fi