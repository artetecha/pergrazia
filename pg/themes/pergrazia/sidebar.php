<?php
/**
 * Sidebar displayed alongside single articles.
 *
 * @package Pergrazia
 */
?>
<aside id="secondary" class="article-sidebar" aria-label="<?php esc_attr_e( 'Article sidebar', 'pergrazia' ); ?>">
	<div class="article-sidebar__inner">
		<?php if ( is_active_sidebar( 'article-sidebar' ) ) : ?>
			<?php dynamic_sidebar( 'article-sidebar' ); ?>
		<?php else : ?>
			<section class="article-sidebar__section">
				<h2 class="sidebar-heading"><?php esc_html_e( 'Latest articles', 'pergrazia' ); ?></h2>
				<?php
				$pergrazia_recent_posts = new WP_Query(
					array(
						'posts_per_page'      => 3,
						'post__not_in'        => array( get_the_ID() ),
						'ignore_sticky_posts' => true,
						'no_found_rows'       => true,
					)
				);
				?>
				<?php if ( $pergrazia_recent_posts->have_posts() ) : ?>
					<ul class="sidebar-posts">
						<?php while ( $pergrazia_recent_posts->have_posts() ) : ?>
							<?php $pergrazia_recent_posts->the_post(); ?>
							<li>
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
							</li>
						<?php endwhile; ?>
					</ul>
				<?php endif; ?>
				<?php wp_reset_postdata(); ?>
			</section>

			<?php
			$pergrazia_categories = get_categories(
				array(
					'hide_empty' => true,
					'number'     => 8,
					'orderby'    => 'count',
					'order'      => 'DESC',
				)
			);
			?>
			<?php if ( $pergrazia_categories ) : ?>
				<section class="article-sidebar__section">
					<h2 class="sidebar-heading"><?php esc_html_e( 'Topics', 'pergrazia' ); ?></h2>
					<ul class="sidebar-categories">
						<?php foreach ( $pergrazia_categories as $pergrazia_category ) : ?>
							<li><a href="<?php echo esc_url( get_category_link( $pergrazia_category->term_id ) ); ?>"><?php echo esc_html( $pergrazia_category->name ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</aside>
