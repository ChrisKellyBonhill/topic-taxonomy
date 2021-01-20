<?php get_header(); ?>
<?php
	$topic = $wp_query->get_queried_object();
	var_dump($topic);
?>
<main id="content">
	<header class="header">
		<h1 class="entry-title"><?php echo $topic->name; ?></h1>
		<div class="archive-meta"><?php echo get_field('excerpt', 'topic_' . $topic->term_id) ?></div>
	</header>
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header>
				<h2 class="entry-title"><a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
				<?php if ( ! is_search() ) { get_template_part( 'entry', 'meta' ); } ?>
			</header>
			<div class="entry-summary">
				<?php if ( has_post_thumbnail() ) : ?>
					<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_post_thumbnail(); ?></a>
				<?php endif; ?>
				<?php // the_excerpt(); ?>
			</div>
			<?php if ( is_singular() ) { get_template_part( 'entry-footer' ); } ?>
		</article>
	<?php endwhile; endif; ?>
	<?php get_template_part( 'nav', 'below' ); ?>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>