<?php
/**
 * Title: Article feed
 * Slug: donne-per-la-grazia/feed
 * Categories: donne-per-la-grazia, query
 * Description: Grid of articles that follows the current page's query. On the first blog page the newest article is featured.
 * Block Types: core/query
 * Inserter: no
 *
 * @package DonnePerLaGrazia
 */

?>
<!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"align":"wide","className":"dplg-feed","layout":{"type":"default"}} -->
<div class="wp-block-query alignwide dplg-feed"><!-- wp:post-template {"style":{"spacing":{"blockGap":"var:preset|spacing|60"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"17rem"}} -->
<!-- wp:post-featured-image {"isLink":true,"aspectRatio":"4/5","className":"is-style-arch"} /-->

<!-- wp:group {"className":"dplg-card__body","style":{"spacing":{"blockGap":"0.75rem"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group dplg-card__body"><!-- wp:post-terms {"term":"category"} /-->

<!-- wp:post-title {"level":2,"isLink":true,"fontSize":"large"} /-->

<!-- wp:post-excerpt {"excerptLength":26} /-->

<!-- wp:post-date /--></div>
<!-- /wp:group -->
<!-- /wp:post-template -->

<!-- wp:query-no-results -->
<!-- wp:pattern {"slug":"donne-per-la-grazia/no-results"} /-->
<!-- /wp:query-no-results -->

<!-- wp:query-pagination {"paginationArrow":"arrow","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"flex","justifyContent":"space-between"}} -->
<!-- wp:query-pagination-previous /-->

<!-- wp:query-pagination-numbers /-->

<!-- wp:query-pagination-next /-->
<!-- /wp:query-pagination --></div>
<!-- /wp:query -->
