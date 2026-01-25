<?php
add_action('admin_menu', function () {
  add_menu_page('IA SEO','IA SEO','manage_options','kzmcito-ia-seo','kzmcito_render_master_prompt');
});
function kzmcito_render_master_prompt() {
  if (isset($_POST['kzmcito_master_prompt'])) {
    update_option('kzmcito_ia_master_prompt', wp_kses_post($_POST['kzmcito_master_prompt']));
    echo '<div class="updated"><p>Guardado.</p></div>';
  }
  $prompt = get_option('kzmcito_ia_master_prompt','');
  echo '<textarea name="kzmcito_master_prompt" style="width:100%;height:400px;">'.esc_textarea($prompt).'</textarea>';
}
