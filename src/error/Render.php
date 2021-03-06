<?php

namespace j\error;

/**
 * Class Render
 * @package j\error
 */
class Render{
    /**
     * @param \Exception $exception
     * @param int $level
     */
    function render($exception, $level){
        if($level != E_ERROR){
            return;
        }

        $this->handleException($exception);
    }

    /**
     * @param \Exception $exception
     */
    protected function handleException($exception){
        $code = $exception->getCode();
        $message = $exception->getMessage();

        $track = sprintf("%s(%d)\n", $exception->getFile(), $exception->getLine());
        $track .= $exception->getTraceAsString();

        $message = "[$code] {$message} \n{$track}";
        if(PHP_SAPI == 'cli'){
            echo $message;
            return;
        }

        $statusCode =  500;
        $statusText = 'Internal Server Error';
        $version = 1.1;
        $charset = "utf-8";

        header("HTTP/{$version} $statusCode {$statusText}");
        header("content-type:text/html;charset={$charset}");
        echo $this->decorate($message);
    }

    /**
     * Get the html response content.
     *
     * @param  string  $content
     * @return string
     */
    protected function decorate($content) {
        return <<<EOF
<!DOCTYPE html>
<html>
<head>
    <meta name="robots" content="noindex,nofollow" />
    <style>
        /* Copyright (c) 2010, Yahoo! Inc. All rights reserved. Code licensed under the BSD License: http://developer.yahoo.com/yui/license.html */
        html{color:#000;background:#FFF;}
        body,div,dl,dt,dd,ul,ol,li,h1,h2,h3,h4,h5,h6,pre,code,form,fieldset,legend,input,textarea,p,blockquote,th,td{margin:0;padding:0;}
        table{border-collapse:collapse;border-spacing:0;}
        fieldset,img{border:0;}
        address,caption,cite,code,dfn,em,strong,th,var{font-style:normal;font-weight:normal;}
        li{list-style:none;}
        caption,th{text-align:left;}
        h1,h2,h3,h4,h5,h6{font-size:100%;font-weight:normal;}
        q:before,q:after{content:'';}
        abbr,acronym{border:0;font-variant:normal;}
        sup{vertical-align:text-top;}
        sub{vertical-align:text-bottom;}
        input,textarea,select{font-family:inherit;font-size:inherit;font-weight:inherit;}
        input,textarea,select{*font-size:100%;}legend{color:#000;}
        html { background: #eee; padding: 10px }
        img { border: 0; }
        #sf-resetcontent { width:970px; margin:0 auto; }
    </style>
</head>
<body>
    <pre>
    {$content}
    </pre>
</body>
</html>
EOF;
    }
}
