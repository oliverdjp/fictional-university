<?php get_header();

      pageBanner(array(
        'title' => 'Welcome to our blog!',
        'subtitle' => 'Keep up with our latest news'
      ));

?>
<div class="container container--narrow page-section">
  <?php
    while(have_posts()){
      the_post(); ?>
      <div class="post-item">
        <h2><a style="text-decoration: none;" class="headline headline--medium headline--post-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <div class="metabox">
          <p>Posted By <?php the_author_posts_link(); ?> on <?php the_time('j F y'); ?> in <?php echo get_the_category_list(', ') ?></p>
        </div>
        <div class="generic-content">
          <?php the_excerpt(); ?>
          <p><a class="btn btn--blue" href="<?php the_permalink(); ?>">Continue reading &raquo;</a></p>
        </div>
      </div>
    <?php } ?>

    <?php
      echo paginate_links();
    ?>

</div>


<?php get_footer(); ?>
