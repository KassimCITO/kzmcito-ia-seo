<?php
add_action('add_meta_boxes', function(){
  add_meta_box('kzmcito_ia_box','IA SEO Multilenguaje','kzmcito_render_ia_box','post','side');
});
function kzmcito_render_ia_box() {
  echo '<p>Generación IA bajo demanda (v1.2)</p>';
}
