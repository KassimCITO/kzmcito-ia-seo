<?php
class Kzmcito_IA_Admin {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'box']);
        add_action('save_post', [$this, 'save'], 10, 2);
    }

    public function box() {
        add_meta_box('kzmcito_ia','IA SEO Multilenguaje',[$this,'render'],'post','side');
    }

    public function render($post) { ?>
        <select name="kzmcito_lang">
            <option value="en">EN</option>
            <option value="fr">FR</option>
            <option value="pt">PT</option>
        </select>
        <button class="button button-primary" name="kzmcito_generate" value="1">Generar IA</button>
    <?php }

    public function save($post_id, $post) {
        if (!isset($_POST['kzmcito_generate']) || $post->post_status!=='publish') return;

        $lang = sanitize_text_field($_POST['kzmcito_lang']);
        $data = Kzmcito_IA_Generator::generate($post,$lang);

        Kzmcito_IA_Cache::set($post_id,$lang,'GLOBAL',$data);

        // RankMath meta
        update_post_meta($post_id,'rank_math_title',$data['meta_title']);
        update_post_meta($post_id,'rank_math_description',$data['meta_description']);
        update_post_meta($post_id,'rank_math_focus_keyword',$data['keywords_used'][0] ?? '');
    }
}
