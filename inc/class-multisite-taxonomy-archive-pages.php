<?php
/**
 * A class to managage multisite taxonomy and multisite terms archive pages
 *
 * TODO: Move this to the community plugin.
 * TODO: Transofrm this class into an abstraction class that creats virtual page rather than injecting content via the_content filter.
 *
 * @package multitaxo
 * @subpackage multisite-taxonomies-frontend
 */

/**
 * Multisite_Taxonomy_Archive_Pages Class.
 */
class Multisite_Taxonomy_Archive_Pages {
	/**
	 * init function.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {
		// Add archive pages related query vars.
		add_filter( 'query_vars', array( $this, 'archive_pages_query_vars' ) );

		// Add rewrite rules for the archive pages.
		add_action( 'init', array( $this, 'add_archive_pages_rewrite_rules' ) );

		// Use the page template for our archive pages.
		add_filter( 'template_include', array( $this, 'archive_pages_template_include' ) );

		// When needed we inject the content of our archive pages.
		add_filter( 'the_content', array( $this, 'archive_pages_content' ) );

		// Hack to avoid the content of archive pages to display multiple times.
		add_filter( 'pre_get_posts', array( $this, 'filtering_posts' ) );

		// Filter the title of archive pages.
		add_filter( 'the_title', array( $this, 'archive_pages_title' ), 20, 2 );

		// Filter for the body class for the archive pages.
		add_filter( 'body_class', array( $this, 'archive_pages_body_class' ) );
	}

	/**
	 * Hack to avoid the content of archive pages to display multiple times.
	 *
	 * @access public
	 * @param WP_Query $wp_query The WP_Query.
	 * @return void
	 */
	public function filtering_posts( $wp_query ) {
		if ( is_multitaxo() ) {
			$wp_query->set( 'posts_per_page', 1 );
			return;
		}
	}

	/**
	 * Add rewrite rules for the archive pages.
	 *
	 * @access public
	 * @return void
	 */
	public function add_archive_pages_rewrite_rules() {
		// TODO: move $base_rewrite as class property.
		// Our base rewrite for all multisite tax plugins.
		$base_rewrite = apply_filters( 'multisite_taxonomy_base_url_slug', 'multitaxo' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals

		// This cannot be empty.
		if ( empty( $base_rewrite ) ) {
			$base_rewrite = 'multitaxo';
		}

		// add our rewrite rules.
		add_rewrite_rule( $base_rewrite . '/([^/]+)/([^/]+)/([0-9]{1,})/?$', 'index.php?multitaxo&multisite_taxonomy=$matches[1]&multisite_term=$matches[2]&mpage=$matches[3]', 'top' );
		add_rewrite_rule( $base_rewrite . '/([^/]+)/([^/]+)/?$', 'index.php?multitaxo&multisite_taxonomy=$matches[1]&multisite_term=$matches[2]', 'top' );
		add_rewrite_rule( $base_rewrite . '/([^/]+)/?$', 'index.php?multitaxo&multisite_taxonomy=$matches[1]', 'top' );
		add_rewrite_rule( $base_rewrite . '/?$', 'index.php?multitaxo', 'top' );
	}

	/**
	 * Add archive pages related query vars.
	 *
	 * @access public
	 * @param array $query_vars Query vars already existing.
	 * @return array Filtered Query vars.
	 */
	public function archive_pages_query_vars( $query_vars ) {
		$query_vars[] = 'multitaxo';
		$query_vars[] = 'multisite_taxonomy';
		$query_vars[] = 'multisite_term';
		$query_vars[] = 'mpage';
		return $query_vars;
	}

	/**
	 * Filter the post content to inject the archive pages content when viwewing them.
	 *
	 * @access public
	 * @param object $post_content The current post content.
	 * @return string The filtered post content.
	 */
	public function archive_pages_content( $post_content ) {
		if ( is_multitaxo() && in_the_loop() ) {
			if ( is_multisite_term() ) {
				return $this->do_multisite_term_archive_page_content();
			} elseif ( is_multisite_taxonomy() ) {
				return $this->do_multisite_taxonomy_archive_page_content();
			} else {
				return $this->do_multisite_taxonomies_archive_page_content();
			}
		} else {
			return $post_content;
		}
	}

	/**
	 * Filter for the body class for the archive pages.
	 *
	 * @access public
	 * @param array $body_classes Current body clases.
	 * @return array Filtered array of body classes.
	 */
	public function archive_pages_body_class( $body_classes ) {
		// this is a single topic page.
		if ( is_multisite_term() ) {
			$body_classes[] = 'multitaxo multisite-term-archive';
		} elseif ( is_multisite_taxonomy() ) {
			$body_classes[] = 'multitaxo multisite-taxonomy-archive';
		} elseif ( is_multisite_taxonomies() ) {
			$body_classes[] = 'multitaxo multisite-taxonomies-archive';
		}

		return $body_classes;
	}

	/**
	 * Use the page template for our archive pages.
	 *
	 * @access public
	 * @param array $template The template determined by WordPress.
	 * @return string The filtered template file.
	 */
	public function archive_pages_template_include( $template ) {

		if ( is_multitaxo() ) {
			return locate_template( array( 'page.php' ) );
		}

		return $template;
	}

	/**
	 * Filter the title of the archive pages.
	 *
	 * @access public
	 * @param string $title The current title.
	 * @return string The filtered title.
	 */
	public function archive_pages_title( $title ) {
		global $wp_query;

		if ( is_multitaxo() && in_the_loop() ) {
			if ( is_multisite_taxonomy() ) {
				// TODO: move $multisite_taxonomy as class property.
				$multisite_taxonomy = get_multisite_taxonomy( sanitize_key( get_query_var( 'multisite_taxonomy' ) ) );
				if ( is_a( $multisite_taxonomy, 'Multisite_Taxonomy' ) ) {
					// translators: The multisite taxonomy name on a multisite taxonomy archive page.
					return wp_sprintf( __( 'All %s: ', 'multitaxo' ), $multisite_taxonomy->labels->name );
				} else {
					// TODO: move check to constructor and return proper 404.
					return __( 'Invalid Multisite Taxonomy', 'multitaxo' );
				}
			} elseif ( is_multisite_term() ) {
				// TODO: move $multisite_taxonomy as class property.
				$multisite_term = get_multisite_term_by( 'slug', sanitize_key( get_query_var( 'multisite_term' ) ), sanitize_key( get_query_var( 'multisite_taxonomy' ) ), OBJECT );
				if ( is_a( $multisite_term, 'Multisite_Term' ) ) {
					// translators: The multisite term name on a multisite term archive page.
					return wp_sprintf( __( 'All articles related to "%s": ', 'multitaxo' ), $multisite_term->name );
				} else {
					// TODO: move check to constructor and return proper 404.
					return __( 'Invalid Multisite Term', 'multitaxo' );
				}
				// TODO: move check to constructor and return proper 404.
				return __( 'Multisite term achive page', 'multitaxo' );
			} else {
				return __( 'All Multisite Taxonomies:', 'multitaxo' );
			}
		}

		// if we reach here then something went oddly wrong.
		return $title;
	}

	/**
	 * Generate an alphabetical list of multisite terms for the current
	 * multisite taxonomy archive page content.
	 *
	 * @access public
	 * @return string The archive page content
	 */
	public function do_multisite_taxonomy_archive_page_content() {
		// Get the taxonomy.
		$multisite_taxonomy = sanitize_key( get_query_var( 'multisite_taxonomy', '' ) );

		// Check that our tax exists.
		if ( false === get_multisite_taxonomy( $multisite_taxonomy ) ) {
			return;
		}

		$topics = get_multisite_terms(
			array(
				'get'        => 'all',
				'orderby'    => 'name',
				'hide_empty' => true,
				'taxonomy'   => $multisite_taxonomy,
			)
		);

		// We make sure we have at least one multisite term.
		if ( is_array( $topics ) && ! empty( $topics ) ) :
			$terms_by_letter = array();
			// We iterate throught all of them and group them by their first letter.
			foreach ( $topics as $topic ) {
				if ( is_a( $topic, 'Multisite_Term' ) ) {
					$terms_by_letter[ strtolower( substr( $topic->name, 0, 1 ) ) ][] = $topic;
				}
			}

			$letters = range( 'a', 'z' );

			// We start buffering the page content.
			ob_start();
			?>

		<div class="alphabetical_index">
			<ul>
				<?php // We create an anchor navigation index. ?>
				<?php
				foreach ( $letters as $letter ) {
					if ( array_key_exists( $letter, $terms_by_letter ) ) {
						?>
						<li><a href="#<?php echo esc_attr( strtolower( $letter ) ); ?>" title="<?php echo esc_attr__( 'View all links in ', 'multitaxo' ) . esc_attr( strtoupper( $letter ) ); ?>"><?php echo esc_attr( strtoupper( $letter ) ); ?></a></li>
						<?php
					} else {
						?>
						<li><span><?php echo esc_attr( strtoupper( $letter ) ); ?></span></li>
						<?php
					}
				}
				?>
			</ul>
		</div>
		<div class="topic-container">
			<?php // Display topic links grouped by letter. ?>
			<?php foreach ( $terms_by_letter as $letter => $topics ) : ?>
				<div class="topic-block">
				<h2 id="<?php echo esc_attr( $letter ); ?>" class="topic-letter"><?php echo esc_attr( strtoupper( $letter ) ); ?></h2>
					<ul class="topic-list">
						<?php if ( is_array( $topics ) && ! empty( $topics ) ) : ?>
							<?php foreach ( $topics as $topic ) : ?>
								<li><a href="<?php echo esc_url( get_multisite_term_link( $topic->multisite_term_id, $topic->multisite_taxonomy ) ); ?>"><?php echo esc_attr( $topic->name ); ?></a></li>
							<?php endforeach; ?>
						<?php endif; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>

			<?php
		else :
			?>
			<p class="exploreWidget"><?php esc_html_e( 'No topics to display.', 'multitaxo' ); ?></a></p>
			<?php
		endif;
		// Get our generated page content.
		$page_content = ob_get_clean();
		return $page_content;
	}

	/**
	 * Generate a list of all registered multisite taxonomies.
	 *
	 * @access public
	 * @return string The archive page content
	 */
	public function do_multisite_taxonomies_archive_page_content() {
		// Get the list of taxonomies.
		$taxonomies = get_multisite_taxonomies( array(), 'objects' );

		// We start buffering the page content.
		ob_start();

		// loop and loop.
		foreach ( $taxonomies as $tax ) {
			$hierarchical = ( true === $tax->hierarchical ) ? 'hierarchical' : 'flat';

			?>
			<div>
				<h2><a href="<?php echo esc_attr( $tax->name ); ?>"><?php echo esc_html( $tax->labels->name ); ?></a></h2>
			</div>
			<?php
		}

		// Get our generated page content.
		$page_content = ob_get_clean();
		return $page_content;
	}

	/**
	 * Generate a list of posts for the multisite term archive page content.
	 *
	 * @access public
	 * @return string The archive page content
	 */
	public function do_multisite_term_archive_page_content() {
		$page_content   = '';
		$multisite_term = get_multisite_term_by( 'slug', sanitize_key( get_query_var( 'multisite_term' ) ), sanitize_key( get_query_var( 'multisite_taxonomy' ) ), OBJECT );
		if ( is_a( $multisite_term, 'Multisite_Term' ) ) {
			$page_content .= self::do_multisite_term_related_terms_list( $multisite_term );
			// TODO: Move this to the class constructor.
			// Get the posts for our multisite term using Multisite_WP_Query.
			$query = new Multisite_WP_Query(
				array(
					'multisite_term_ids' => array( $multisite_term->multisite_term_id ),
					'nopaging'           => true,
					'orderby'            => 'post_date',
					'order'              => 'DESC',
				)
			);
			if ( isset( $query->posts ) && is_array( $query->posts ) && ! empty( $query->posts ) ) {
				// TODO: Move this to the class constructor.
				$posts_per_page  = (int) get_option( 'posts_per_page', 10 );
				$number_of_posts = count( $query->posts );
				$current_page    = (int) get_query_var( 'mpage', 1 );
				$offset          = ( $current_page - 1 ) * $posts_per_page;

				// We split the full list of posts into multiple arrays of $posts_per_page number of posts.
				$posts           = array_chunk( $query->posts, $posts_per_page );
				$number_of_pages = (int) ceil( $number_of_posts / $posts_per_page );

				// Substract 1 to page number to match php array keys.
				$current_page_key = $current_page - 1;
				if ( isset( $posts[ $current_page_key ] ) ) {
					$page_content .= self::do_multisite_term_posts_list( $posts[ $current_page_key ] );
					$page_content .= self::do_multisite_term_archive_page_pagination( $current_page, $number_of_pages, $multisite_term );
				} else {
					// TODO: Once the query and pagination var are moved to constructor check this earlier and return proper 404.
					$page_content .= __( 'This multisite term currently has no post.', 'multitaxo' );
				}
			} else {
				// TODO: Once the query and pagination var are moved to constructor check this earlier and return proper 404.
				$page_content .= __( 'This multisite term currently has no post.', 'multitaxo' );
			}

			return $page_content;
		} else {
			return __( 'This multisite term doesn\'t seem to exist', 'multitaxo' );
		}
	}

	/**
	 * Get an array of arrays of related multisite terms grouped per multisite taxonomies for a given multisite term.
	 *
	 * @access public
	 * @param int $multisite_term_id The multisite term for which we display related terms.
	 * @return array Related multisite terms grouped per multisite taxonomies.
	 */
	public static function get_multisite_term_related_multisite_terms( $multisite_term_id ) {

		$related_terms = array();

		// Get the related terms as a list of multisite terms ids grouped per multisite taxonomies.
		$related_terms_ids = get_multisite_term_meta( $multisite_term_id, 'related_topics', true );

		if ( is_array( $related_terms_ids ) && ! empty( $related_terms_ids ) ) {
			foreach ( $related_terms_ids as $multisite_taxonomy => $multisite_terms ) {
				foreach ( $multisite_terms as $multisite_term ) {
					$current_multisite_term = get_multisite_term( $multisite_term, $multisite_taxonomy );
					if ( is_a( $current_multisite_term, 'Multisite_Term' ) ) {
						$related_terms[] = $current_multisite_term;
					}
				}
			}
		}

		return $related_terms;
	}

	/**
	 * Generate a list of related mulsite terms for a given term.
	 *
	 * @access public
	 * @param Multisite_Term $multisite_term The multisite term for which we display related terms.
	 * @param boolean        $new_window Should the links open in a new window (true/false - default to false).
	 * @return string The archive page content.
	 */
	public static function do_multisite_term_related_terms_list( $multisite_term, $new_window = false ) {
		// get the realted topics.
		$related_terms = self::get_multisite_term_related_multisite_terms( $multisite_term->multisite_term_id );

		// We start buffering the page content.
		ob_start();
		if ( is_array( $related_terms ) && ! empty( $related_terms ) ) :
			$target_attr = '_self';
			if ( true === $new_window ) {
				$target_attr = '_blank';
			}
			?>
			<div class="topic-block">
				<h2 class="topic-letter"><?php esc_html_e( 'Related Topics', 'multitaxo' ); ?></h2>
				<ul class="topic-list">
					<?php foreach ( $related_terms as $related_multisite_term ) : ?>
						<li>
							<a target="<?php echo esc_attr( $target_attr ); ?>" href="<?php echo esc_url( get_multisite_term_link( $related_multisite_term ) ); ?>">
							<?php echo esc_html( $related_multisite_term->name ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php
		endif;
		// Get our generated page content.
		$page_content = ob_get_clean();
		return $page_content;
	}

	/**
	 * Generate a list of posts in the mulsitite archive page context.
	 *
	 * @access public
	 * @param array $posts  An array of posts to be displayed as a list.
	 * @return string HTML list of posts.
	 */
	public static function do_multisite_term_posts_list( $posts ) {
		$page_content = '';
		if ( is_array( $posts ) ) {
			ob_start();
			// We display the posts of the current page.
			foreach ( $posts as $post ) :
				// handle pinning.
				$pinned_class = '';

				if ( isset( $post->pinned_story ) && true === $post->pinned_story ) {
					$pinned_class = 'pinned-story';
				}
				?>
					<article id="post-<?php multitaxo_the_id( $post->ID ); ?>" aria-label="<?php esc_attr_e( 'Excerpt of the article:', 'multitaxo' ); ?> <?php echo esc_attr( multitaxo_get_the_title( $post->post_title ) ); ?>"  class="post-<?php multitaxo_the_id( $post->ID ); ?> post multisite-term <?php echo esc_attr( $pinned_class ); ?>">
						<header class="entry-header">
							<?php
							$display_thumbnails = apply_filters( 'hsph_plugin_tagging_display_thumbnails_posts_lists', true );
							if ( is_array( $post->post_thumbnail ) && ! empty( $post->post_thumbnail['url'] ) && $display_thumbnails ) {
								multitaxo_the_post_thumbnail( $post );
							}
							// Filter allowing overide of the target on links. Defaults to same window (_self).
							$link_target = apply_filters( 'hsph_plugin_tagging_link_target_posts_lists', '_self' );

							// Pinned story.
							if ( isset( $post->pinned_story ) && true === $post->pinned_story ) {
								?>
									<div class="pinned-title"><span class="dashicons dashicons-admin-post"></span> <?php esc_html_e( 'Pinned', 'multitaxo' ); ?></div>
								<?php
							}
							?>
							<h2 class="entry-title"><a target="<?php echo esc_attr( $link_target ); ?>" href="<?php multitaxo_the_permalink( $post->post_permalink ); ?>" rel="bookmark" title="<?php echo esc_attr( __( 'Permalink to ', 'multitaxo' ) . multitaxo_get_the_title( $post->post_title ) ); ?>"><?php multitaxo_the_title( $post->post_title ); ?></a></h2>
						</header><!-- .entry-header -->
						<div class="entry-summary">
							<?php multitaxo_the_excerpt( $post ); ?>
						</div><!-- .entry-summary -->
					</article>
				<?php
			endforeach; // Post loop.
			// Get our generated page content.
			$page_content = ob_get_clean();
		}
		return $page_content;
	}

	/**
	 * Return the html page pagination for the multisite term archive page.
	 *
	 * @param  int            $current_page The current page number.
	 * @param  int            $number_of_pages The total number of pages.
	 * @param  Multisite_Term $multisite_term The multisite term for which we display the archive page.
	 * @return string The html string for page pagination.
	 */
	public static function do_multisite_term_archive_page_pagination( $current_page, $number_of_pages, $multisite_term ) {
		// setup the params for pagination.
		$pagination_args = array(
			'format'    => '%#%',
			'total'     => absint( $number_of_pages ),
			'current'   => absint( $current_page ),
			'show_all'  => false,
			'end_size'  => 3,
			'mid_size'  => 2,
			'prev_next' => true,
			'prev_text' => __( '&laquo; Previous', 'multitaxo' ),
			'next_text' => __( 'Next &raquo;', 'multitaxo' ),
			'type'      => 'plain',
		);

		$pagination_args ['base'] = get_multisite_term_link( $multisite_term ) . '%_%/';

		// add the pagination to the page.
		ob_start();
		?>
		<nav id="term-<?php echo absint( $multisite_term->multisite_term_id ); ?>" class="navigation pagination" role="navigation">
			<h3 class="assistive-text screen-reader-text"><?php esc_html_e( 'Post navigation', 'multitaxo' ); ?></h3>
			<div class="nav-links">
				<?php echo paginate_links( $pagination_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</nav>
		<?php
		// Get our generated page content.
		$page_content = ob_get_clean();
		return $page_content;
	}
}
