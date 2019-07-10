<?php

// class View to return content in html mixed to template

class View
{

    public static function makeHtml($data, $template){
        //replace in the template the {{ ... }} by the values
        return str_replace(array_keys($data), $data, file_get_contents("template/$template.html"));
    }

    public static function makeLoopHtml($data, $template){
        $html = "";
        foreach ($data as $value) {
            $html .= self::makeHtml($value, $template);
        }
        return $html;
    }

    public static function giveHtml($template){
        return file_get_contents("template/$template.html");
    }

    public static function addBackTpl($html){
        return View::makeHtml([
                        "{{ path }}" => $GLOBALS["path"],
                        "{{ content_admin_page }}" => $html
                    ],"backadmin_template");
    }

    public static function errorDisplayBack(){
        return View::makeHtml([
                        "{{ path }}" => $GLOBALS["path"]
                        ], "back_message_error");
    }

}

