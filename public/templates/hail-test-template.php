<?php get_header(); ?>

<?php

// hail helper
$hail = Hail_Helper::getInstance();

$post_id = get_the_ID();
$hail_id = get_post_meta($post_id, 'hail_id', true);
$title = get_the_title();
$lead = get_post_meta($post_id, 'lead', true);
$body = get_post_meta($post_id, 'body', true);
$hero_url = get_post_meta($post_id, 'hero_url', true);

?>


<div id="content" class="site-content">
  <div class="grid">
    <div class="row">
      <div class="eight column content-area">
        <div class="primary">
          <div id="main" class="site-main" role="main">

            <article>

              <h1>Hello..</h1>

              <?php if ($hero_url) { ?>

                <div class="featured_image">
                  <img src="<?php echo $hero_url ?>">
                </div>

              <?php } ?>

              <div class="entry-content">
                <header class="entry-header">
                  <h1 class="entry-title"><?php echo $title; ?></h1>
                </header>

                <p><?php echo $lead; ?></p>

                <?php echo $body; ?>
              </div>


              <div class="entry-content">
                <div class="entry-content-wrapper">
                  <p>
                    <?php
                    $images = $hail->getArticleImages($hail_id);

                    foreach ($images as $image) {
                      // echo '<div class="gallery-item">';
                      echo '<img class="align-none size-post-thumbnail" src="' . $image['file_500_square_url'] . '">';
                      // echo '</div>';
                    }
                    ?>
                  </p>
                </div>
              </div>


            </article>

          </div> <!-- #main -->
        </div> <!-- .primary -->
      </div> <!-- .content-area -->

      <?php get_template_part('sidebars/sidebar', 'primary'); ?>

    </div> <!-- .row -->
  </div> <!-- .grid -->
</div> <!-- .site-content -->


<?php get_footer(); ?>
