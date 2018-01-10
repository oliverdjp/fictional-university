<?php
require get_theme_file_path('/inc/search-route.php');
require get_theme_file_path('/inc/like-route.php');
//Adding new field to the JSON request
function university_custom_rest(){
    register_rest_field('post', 'authorName', array(
      'get_callback' => function(){
          return get_the_author();
        }
    ));
    register_rest_field('note', 'userNoteCount', array(
      'get_callback' => function(){
          return count_user_posts(get_current_user_id(), 'note');
        }
    ));
}
add_action('rest_api_init', 'university_custom_rest');

  function pageBanner($args = NULL){ //$args = NULL makes the argument optional
    if(!$args['title']){
      $args['title'] = get_the_title();
    }
    if(!$args['subtitle']){
      $args['subtitle'] = get_field('page_banner_subtitle');
    }
    if(!$args['photo']){
      if(get_field('page_banner_background_image')){
        $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
      } else {
      $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
      }
    }
    ?>
    <div class="page-banner">
      <div class="page-banner__bg-image" style="background-image: url(
        <?php
          echo $args['photo'];
        ?>">
      </div>
      <div class="page-banner__content container container--narrow">
        <h1 class="page-banner__title"><?php echo $args['title'] ?></h1>
        <div class="page-banner__intro">
          <p><?php echo $args['subtitle']; ?></p>
        </div>
      </div>
    </div>
    <?php
  }


  //Calling resources
  function university_files(){
    wp_enqueue_script('googleMap', '//maps.googleapis.com/maps/api/js?key=AIzaSyDiVvRw-UswnNMymw9T3D-5N_NfYGW0Y8A', NULL, '1.0', true);
    wp_enqueue_script('main-university-js', get_theme_file_uri('/js/scripts-bundled.js'), NULL, '1.0', true);
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    wp_enqueue_style('font-awesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('university-main-styles', get_stylesheet_uri());
    wp_localize_script('main-university-js', 'universityData', array(
      'root_url' => get_site_url(),
      'nonce' => wp_create_nonce('wp_rest')
    ));
  }
  add_action('wp_enqueue_scripts', 'university_files');
  function university_features(){
    //Enabling the title feature.
    add_theme_support('title-tag');
    //Featured Images
    add_theme_support('post-thumbnails');
    add_image_size('professorLandscape', 400, 260, true);
    add_image_size('professorPortrait', 480, 650, true);
    add_image_size('pageBanner', 1500, 350, true);
    //Enabling the ability to create menus in the admin dashboard
    // register_nav_menu('headerMenuLocation', 'Header Menu Location');
    // register_nav_menu('footerLocationOne', 'Footer Location One');
    // register_nav_menu('footerLocationTwo', 'Footer Location Two');
  }
  add_action('after_setup_theme', 'university_features');

  function university_adjust_queries($query){
    if(!is_admin() AND is_post_type_archive('campus') AND $query->is_main_query()){
      $query->set('posts_per_page', -1);
    }
    if(!is_admin() AND is_post_type_archive('program') AND $query->is_main_query()){
      $query->set('orderby', 'title');
      $query->set('order', 'ASC');
      $query->set('posts_per_page', -1);
    }
    if(!is_admin() AND is_post_type_archive('event') AND $query->is_main_query()){
      $today = date('Ymd');
      $query->set('meta_key','event_date');
      $query->set('orderby','meta_value_num');
      $query->set('order', 'ASC');
      $query->set('posts_per_page', '10');
      $query->set('meta_query', array(
        'key' => 'event_date',
        'compare' => '>=',
        'value' => $today,
        'type' => 'numeric'
      ));
    }
  }
  add_action('pre_get_posts', 'university_adjust_queries');

  //Google Api Key
  function universityMapKey($api){
    $api['key'] = 'AIzaSyDiVvRw-UswnNMymw9T3D-5N_NfYGW0Y8A';
    return $api;
  }
  add_filter('acf/fields/google_map/api', 'universityMapKey');

  //Redirect subscriber accounts to the homepage
  function redirectSubsToFrontEnd(){
    $currentUser = wp_get_current_user();
    if(count($currentUser->roles) == 1 AND $currentUser->roles[0] == 'subscriber'){
      wp_redirect(site_url('/'));
      exit;
    }
  }
  add_action('admin_init', 'redirectSubsToFrontEnd');

  //Remove admin bar for sub users
  function noSubsAdminBar(){
    $currentUser = wp_get_current_user();
    if(count($currentUser->roles) == 1 AND $currentUser->roles[0] == 'subscriber'){
      show_admin_bar(false);
    }
  }
  add_action('wp_loaded', 'noSubsAdminBar');

  //Customize Login Screen
  function ourHeaderUrl(){
    return esc_url(site_url('/'));
  }
  add_filter('login_headerurl', 'ourHeaderUrl');

  //Load the Login CSS
  function ourLoginCSS(){
    wp_enqueue_style('university-main-styles', get_stylesheet_uri());
    wp_enqueue_style('custom-google-fonts', '//fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
  }
  add_action('login_enqueue_scripts', 'ourLoginCSS');

  function ourLoginTitle(){
    return get_bloginfo('name');
  }
  add_filter('login_headertitle', 'ourLoginTitle');

  //Force Note Posts to be private
  function makeNotePrivate($data, $postarr){
    if($data['post_type'] == 'note'){
      $data['post_title'] = sanitize_text_field($data['post_title']);
      $data['post_content'] = sanitize_textarea_field($data['post_content']);
      if(count_user_posts(get_current_user_id(), 'note') > 4 AND !$postarr['ID']){
        die("You have reached your note limit.");
      }
    }
    if($data['post_type'] == 'note' AND $data['post_status'] != 'trash'){
      $data['post_status'] = 'private';
      }
    return $data;
  }
  add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2);
?>
