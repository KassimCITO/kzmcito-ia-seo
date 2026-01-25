<?php
class Kzmcito_IA_Cache {

    public static function key($lang,$variant='GLOBAL'){
        return '_kzmcito_ia_'.$lang.'_'.$variant;
    }

    public static function set($post_id,$lang,$variant,$data){
        $data['generated_at']=time();
        update_post_meta($post_id,self::key($lang,$variant),$data);
    }

    public static function get($post_id,$lang,$variant='GLOBAL'){
        return get_post_meta($post_id,self::key($lang,$variant),true);
    }
}
