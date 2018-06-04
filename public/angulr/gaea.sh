#!/bin/sh
#
# gaea mgr - this script starts and stops the gaea frontend
#

source /etc/profile

# Source function library.
os=`uname -s`
if [ $os != "Darwin" ];then
    . /etc/rc.d/init.d/functions
fi

dest_dir=../
angular_dir=angular
angular_files='api css fonts img angular.php js l10n tpl ueditor harviewer dialogs lang php themes third-party ueditor*.js'
bower_components_dir=bower_components
node_module=node_modules
proc=gaea


install() {
    echo -n $"Starting install proc: "
    cp -r $angular_dir/* $dest_dir &&
    mv $dest_dir/index.html $dest_dir/angular.php &&
    cp -r $bower_components_dir $dest_dir
    cp -r $node_module  $dest_dir
    retval=$?
    if [ $os != "Darwin" ];then
        [ $retval -eq 0 ] && success || failure
    fi
    echo
}

compile() {
    /usr/local/bin/grunt build:angular
	cp -r ueditor $angular_dir
	cp -r harviewer $angular_dir
}

clean() {
    echo -n $"Starting clean $proc: "
    /usr/local/bin/grunt clean
    for i in $angular_files
    do
        rm -rf $dest_dir/$i
    done
    rm -rf $dest_dir/$bower_components_dir
    rm -rf $dest_dir/$node_module
    retval=$?
    if [ $os != "Darwin" ];then
        [ $retval -eq 0 ] && success || failure
    fi
    echo 
}

refresh() {
    install_components
    compile
    install
}


install_components() {
    #bower install
    npm install --registry=http://npm.intra.sit.ffan.com
    echo ''
}



case "$1" in
    compile)
        $1
        ;;
    install)
        $1
        ;;
    clean)
        $1
        ;;
    refresh)
        $1
        ;;
    *)
        echo $"Usage: $0 {compile|install|clean|refresh}"
        exit 2
esac
