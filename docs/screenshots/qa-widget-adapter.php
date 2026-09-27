<?php
if ( ! defined( "ABSPATH" ) ) { exit; }
/** Local QA adapter for exercising a classic widget inside the Osmium block theme. */
add_shortcode('brpw_qa_widget', function() {
 ob_start();
 the_widget('BRP_Widget', array('title'=>'Recent stories','totalnews'=>3,'textbutton'=>'All stories','pageid'=>18), array('before_widget'=>'<aside class="brpw-qa-sidebar">','after_widget'=>'</aside>','before_title'=>'<h2>','after_title'=>'</h2>'));
 return ob_get_clean();
});
add_shortcode('brpw_qa_variants', function() {
 $settings=array('category'=>4,'totalnews'=>3,'show_comments'=>false,'show_excerpt'=>true);
 return '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:32px"><section style="background:#123f3a;color:#faf7ef;padding:24px"><h2 style="color:inherit">Evening reading</h2>'.brpw_render($settings).'</section><section dir="rtl" style="padding:24px"><h2>Right-to-left layout</h2>'.brpw_render($settings).'</section></div>';
});
