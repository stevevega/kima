#!/bin/bash

#------------------------------------------------------------------------------
# Name:    kima project creator
# Version: 1.0
# Purpose: Create a new Kima project
# Author:  Steve Vega
#------------------------------------------------------------------------------

declare -r TRUE=0
declare -r FALSE=1

# gets whether a user responds yes or no
function get_response()
{
    local response=$1
    [ $response = "y" ] && echo $TRUE; return
    [ $response = "Y" ] && echo $TRUE; return
    [ $response = "yes" ] && echo $TRUE; return
    echo $FALSE
}

echo "Welcome to Kima, let's create your new project."

echo ""
read -p "Enter your project name/directory: " name
name=${name:-kima}

read -p "Create Apache .htaccess? (Y/n): " create_htaccess
create_htaccess=${create_htaccess:-y}

read -p "Create .gitignore File? (Y/n): " create_git_ignore
create_git_ignore=${create_git_ignore:-y}

read -p "Create README.md File? (Y/n): " create_readme
create_readme=${create_readme:-y}

echo ""
echo "-----------------------------------------------"
echo "Creating new Kima Project"
echo "Project name/directory: $name"
echo "Create Apache .htaccess?: $create_htaccess"
echo "Create .gitignore File?: $create_git_ignore"
echo "Create README.md File?: $create_readme"
echo "-----------------------------------------------"

mkdir -p ${name}/application/{config,controller,model,module,view/index}
mkdir -p ${name}/data/{cache/template,db/{model,sql},file,log}
mkdir -p ${name}/docs
mkdir -p ${name}/library
mkdir -p ${name}/public/{css,img,js}
mkdir -p ${name}/resource/l10n
mkdir -p ${name}/scripts/jobs
mkdir -p ${name}/temp

#---------------------------------
# create the application ini file
#---------------------------------
echo "; Application ini
[default]
; application
application.folder = ROOT_FOLDER'/application'
controller.folder = ROOT_FOLDER'/application/controller'
module.folder = ROOT_FOLDER'/application/module'
l10n.folder = ROOT_FOLDER'/resource/l10n/'

; ISO 639-1
language.default = en
;language.available =

; cache
cache.default = file
cache.folder = ROOT_FOLDER'/data/cache'
;cache.memcached.server.localhost.host = localhost
;cache.memcached.server.localhost.port = 11211
;cache.memcached.server.localhost.weight = 100
;cache.prefix = kima

; database default
;database.default = mysql

; database mysql
;database.mysql.name = db_name
;database.mysql.host = 127.0.0.1
;database.mysql.user = user
;database.mysql.password = pass
;database.mysql.port = 3306

; database mongo
;database.mongo.name = db_name
;database.mongo.host = 127.0.0.1
;database.mongo.user = user
;database.mongo.password = pass

; search solr
;search.solr.kima.hostname = 127.0.0.1
;search.solr.kima.login = user
;search.solr.kima.password = pass
;search.solr.kima.port = 8983
;search.solr.kima.path = solr/core0

; view
view.layout = layout.html
view.folder = ROOT_FOLDER'/application/view'
view.autodisplay = true
view.compression = true

; Put your environment config here
[YOUR_ENVIRONMENT]
view.compression = false" > ${name}/application/config/application.ini

#------------------------------
# create the index.php file
#------------------------------
echo "<?php
/**
 * Namespaces to use
 */
use \Kima\Application;
use \Kima\Config;
use \Kima\Template;

// Define path to application directory
if (!defined('ROOT_FOLDER'))
{
    define('ROOT_FOLDER', realpath(dirname(__FILE__) . '/..'));
}

// Set the library directory to the include path
set_include_path(implode(PATH_SEPARATOR,
    [realpath(ROOT_FOLDER . '/library'), get_include_path()]));

// put your url routing here
\$urls = [
      '/' => 'Index',
      '/index/([A-Za-z0-9]+)/' => 'Index'];

require_once('Kima/Application.php');
\$application = Application::get_instance()->run(\$urls);" > ${name}/public/index.php

#------------------------------
# create application Bootstrap
#------------------------------
echo "<?php
/**
 * Kima Application Bootstrap
 */

/**
 * Application Bootstrap
 */
class Bootstrap
{

    /**
     * Test bootstrap method
     */
    public function test()
    {
        // method code
    }

}" > ${name}/application/Bootstrap.php

#------------------------------
# create index controller
#------------------------------
echo "<?php
/**
 * Namespaces to use
 */
use \Kima\Controller;

/**
 * Index
 */
class Index extends Controller
{

    /**
     * get test
     */
    public function get(\$params)
    {
        # set title
        \$this->view->set('title', 'Welcome to Kima!');
        \$this->view->show('title');

        # display content
        \$this->view->show('content');
    }

}" > ${name}/application/controller/Index.php

#------------------------------
# create index view
#------------------------------
echo "<!-- begin:content -->
<div id=\"main\">
    Visit https://github.com/stevevega/kima for more information
</div>
<!-- end:content -->" > ${name}/application/view/index/get.html

#------------------------------
# create layout
#------------------------------
echo "<!-- begin:main -->
<!DOCTYPE html>
<html>
    <head>
        <title>[title]</title>
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
    </head>
    <body>
        <!-- begin:title -->
        <h1>{title}</h1>
        <!-- end:title -->

        <!-- include:content -->

    </body>
</html>
<!-- end:main -->" > ${name}/application/view/layout.html

#------------------------------
# create error handler controller
#------------------------------
echo "<?php
/**
 * Namespaces to use
 */
use \Kima\Controller,
    \Kima\Http\StatusCode;

/**
 * Error
 */
class Error extends Controller
{

    /**
     * get
     */
    public function get()
    {
        \$status_code = http_response_code();
        \$status_message = StatusCode::get_message(\$status_code);
        echo 'Error ' . \$status_code . ': ' . \$status_message;
    }

}" > ${name}/application/controller/Error.php

#------------------------------
# create locale strings default
#------------------------------
echo "[global]
title = \"Welcome to Kima!\"" > ${name}/resource/l10n/en.ini

#------------------------------
# create .git ignore
#------------------------------
if [ "$(get_response $create_git_ignore)" = "$TRUE" ]; then
echo "data/cache
data/log" > ${name}/.gitignore
fi

#-----------------------------
# create Apache .htaccess, if desired
#-----------------------------
if [ "$(get_response $create_htaccess)" = "$TRUE" ]; then
echo "<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_URI} !(.*)/$
    RewriteRule ^(.*)$ /\$1/ [L,R=301]

    SetEnvIf Host \"([a-zA-Z-_]+).kima$\" MODULE=\$1

    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . index.php
</IfModule>

<IfModule !mod_rewrite.c>
</IfModule>" > ${name}/public/.htaccess
fi

#-----------------------------
# create README.me, if desired
#-----------------------------
if [ "$(get_response $create_readme)" = "$TRUE" ]; then
touch ${name}/README.md
fi

echo ""
echo "Project ${name} was created. Enjoy Kima!"
echo "Make sure add Kima in your include path or copy it to your library folder."
echo "For more information visit https://github.com/stevevega/kima"
