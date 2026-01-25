<?php
class Kzmcito_IA_Frontend {

    public function __construct(){
        add_filter('the_content',[$this,'content'],20);
    }

    public function content($content){
        if(!is_singular()) return $content;
        $lang=$_COOKIE['kzmcito_lang']??'es';
        if($lang==='es') return $content;
        $c=Kzmcito_IA_Cache::get(get_the_ID(),$lang);
        return $c['content_html']??$content;
    }
}
