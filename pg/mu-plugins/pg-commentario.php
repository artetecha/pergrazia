<?php
/**
 * Plugin Name: Per Grazia — Commentario
 * Description: Commentario biblico ordinato per brano, indipendente dal blog.
 */

defined( 'ABSPATH' ) || exit;

final class PG_Commentario {

	const TYPE = 'pg_commentario';
	private static bool $syncing = false;
	private static array $expected_slugs = array();
	private static array $rest_meta = array();
	private static array $rejected_panels = array();

	public static function init(): void {
		register_post_type( self::TYPE, array(
			'labels'       => array(
				'name' => 'Commentari', 'singular_name' => 'Puntata', 'menu_name' => 'Commentari',
				'add_new' => 'Aggiungi', 'add_new_item' => 'Aggiungi una puntata',
				'edit_item' => 'Modifica la puntata', 'new_item' => 'Nuova puntata',
				'view_item' => 'Visualizza la puntata', 'view_items' => 'Visualizza il commentario',
				'search_items' => 'Cerca nel commentario', 'not_found' => 'Nessuna puntata trovata.',
				'not_found_in_trash' => 'Nessuna puntata nel cestino.', 'all_items' => 'Libri e puntate',
				'parent_item_colon' => 'Libro:', 'archives' => 'Commentari',
				'attributes' => 'Attributi della puntata', 'insert_into_item' => 'Inserisci nella puntata',
				'uploaded_to_this_item' => 'Caricati per questa puntata',
			),
			'public'       => true,
			'hierarchical' => true,
			'show_in_rest' => true,
			'has_archive'  => 'commentari',
			'rewrite'      => array( 'slug' => 'commentari', 'with_front' => false ),
			'menu_icon'    => 'dashicons-book-alt',
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'comments', 'page-attributes', 'custom-fields' ),
		) );

		foreach ( array( 'pg_book', 'pg_kind', 'pg_ref', 'pg_ref_start', 'pg_ref_end' ) as $key ) {
			$integer = in_array( $key, array( 'pg_book', 'pg_ref_start', 'pg_ref_end' ), true );
			$derived = in_array( $key, array( 'pg_ref_start', 'pg_ref_end' ), true );
			register_post_meta( self::TYPE, $key, array(
				'type' => $integer ? 'integer' : 'string', 'single' => true,
				'default' => $integer ? 0 : '',
				'show_in_rest' => array( 'schema' => array( 'readonly' => $derived ) ),
				'sanitize_callback' => $integer ? array( self::class, 'sanitize_integer' ) : 'sanitize_text_field',
				'auth_callback' => static function ( $allowed, $meta_key, $post_id ) use ( $derived ) {
					return ! $derived && current_user_can( 'edit_post', $post_id );
				},
			) );
		}
	}

	public static function sanitize_integer( $value ): int {
		$integer = filter_var( $value, FILTER_VALIDATE_INT );
		return false === $integer ? -1 : $integer;
	}

	/** Parse a single verse or continuous range; never accept partial matches. */
	public static function parse_reference( string $ref, int $book ) {
		$ref = trim( str_replace( array( '–', '—' ), '-', $ref ) );
		if ( $book < 1 || $book > 66 || ! preg_match( '/^([1-9][0-9]{0,2}):([1-9][0-9]{0,2})(?:\s*-\s*(?:([1-9][0-9]{0,2}):)?([1-9][0-9]{0,2}))?$/D', $ref, $m ) ) {
			return new WP_Error( 'pg_reference', 'Riferimento non valido. Usa «1:1-14» oppure «1:12-2:4».' );
		}
		$c1 = (int) $m[1];
		$v1 = (int) $m[2];
		$c2 = empty( $m[3] ) ? $c1 : (int) $m[3];
		$v2 = empty( $m[4] ) ? $v1 : (int) $m[4];
		$start = $book * 1000000 + $c1 * 1000 + $v1;
		$end   = $book * 1000000 + $c2 * 1000 + $v2;
		if ( $end < $start ) {
			return new WP_Error( 'pg_reference', 'La fine del brano deve seguire il suo inizio.' );
		}
		return array(
			'start' => $start, 'end' => $end,
			'slug' => $c1 === $c2 ? "$c1-$v1-$v2" : "$c1-$v1-$c2-$v2",
		);
	}

	private static function metadata( int $id ): array {
		return array(
			'pg_book' => (int) get_post_meta( $id, 'pg_book', true ),
			'pg_kind' => (string) get_post_meta( $id, 'pg_kind', true ),
			'pg_ref'  => (string) get_post_meta( $id, 'pg_ref', true ),
		);
	}

	/** Validate a complete prospective state before persisting editorial metadata. */
	public static function validate( int $id, int $parent, array $meta, string $status ) {
		$book = (int) ( $meta['pg_book'] ?? 0 );
		$kind = (string) ( $meta['pg_kind'] ?? '' );
		$ref  = trim( (string) ( $meta['pg_ref'] ?? '' ) );
		$public = in_array( $status, array( 'publish', 'future', 'private' ), true );
		if ( ! $parent ) {
			if ( ( $public || $book ) && ( $book < 1 || $book > 66 ) ) {
				return new WP_Error( 'pg_book', 'Indica il numero del libro nel canone protestante (1–66; Atti = 44).' );
			}
			if ( $kind || $ref ) {
				return new WP_Error( 'pg_parent', 'Seleziona un libro genitore per l’introduzione, la puntata o l’excursus.' );
			}
			return array( 'start' => 0, 'end' => 0, 'order' => $book, 'slug' => '' );
		}
		$book_post = get_post( $parent );
		$book = (int) get_post_meta( $parent, 'pg_book', true );
		if ( $parent === $id || ! $book_post || self::TYPE !== $book_post->post_type || $book_post->post_parent || $book < 1 || $book > 66 ) {
			return new WP_Error( 'pg_parent', 'Il genitore deve essere un libro del commentario con un numero canonico valido.' );
		}
		if ( ! in_array( $kind, array( '', 'introduzione', 'puntata', 'excursus' ), true ) || ( $public && '' === $kind ) ) {
			return new WP_Error( 'pg_kind', 'Seleziona «Introduzione», «Puntata» oppure «Excursus».' );
		}
		if ( 'introduzione' === $kind && '' !== $ref ) {
			return new WP_Error( 'pg_reference', 'Lascia vuoto il riferimento dell’introduzione.' );
		}
		if ( 'puntata' === $kind && '' === $ref && $public ) {
			return new WP_Error( 'pg_reference', 'Indica il brano della puntata, per esempio «1:1-14».' );
		}
		$range = '' === $ref ? array( 'start' => 0, 'end' => 0, 'slug' => '' ) : self::parse_reference( $ref, $book );
		if ( is_wp_error( $range ) ) {
			return $range;
		}
		$range['order'] = 'introduzione' === $kind ? 0 : $range['start'];
		if ( 'introduzione' === $kind ) {
			$range['slug'] = 'introduzione';
		} elseif ( 'excursus' === $kind ) {
			$range['slug'] = ''; // Its descriptive name comes from the title, not the passage.
		}
		return $range;
	}

	private static function notice( string $message ): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::warning( $message );
		}
		if ( get_current_user_id() ) {
			set_transient( 'pg_commentario_notice_' . get_current_user_id(), $message, 300 );
		}
	}

	public static function admin_notice(): void {
		$key = 'pg_commentario_notice_' . get_current_user_id();
		$message = get_transient( $key );
		if ( $message ) {
			delete_transient( $key );
			echo '<div class="notice notice-error"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	private static function panel(): ?array {
		if ( ! isset( $_POST['pg_commentario_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pg_commentario_nonce'] ) ), 'pg_commentario' ) ) {
			return null;
		}
		return array(
			'pg_book' => self::sanitize_integer( empty( $_POST['pg_book'] ) ? 0 : $_POST['pg_book'] ),
			'pg_kind' => sanitize_text_field( wp_unslash( $_POST['pg_kind'] ?? '' ) ),
			'pg_ref' => sanitize_text_field( wp_unslash( $_POST['pg_ref'] ?? '' ) ),
		);
	}

	public static function insert_data( array $data, array $postarr ): array {
		if ( self::$syncing || self::TYPE !== $data['post_type'] || 'auto-draft' === $data['post_status'] || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return $data;
		}
		$id = (int) ( $postarr['ID'] ?? 0 );
		$panel = self::panel();
		if ( $panel && ! current_user_can( 'edit_post', $id ) ) {
			$panel = null;
		}
		// REST persists metadata after the post; use the already validated incoming state.
		$meta = array_merge( self::metadata( $id ), $postarr['meta_input'] ?? array(), $panel ?? array(), self::$rest_meta[ $id ] ?? array() );
		$state = self::validate( $id, (int) $data['post_parent'], $meta, $data['post_status'] );
		if ( is_wp_error( $state ) ) {
			self::notice( $state->get_error_message() . ' I dati del brano non sono stati salvati.' );
			if ( null !== $panel ) {
				self::$rejected_panels[ $id ] = true;
			}
			$old = get_post( $id );
			$data['post_status'] = $old ? $old->post_status : 'draft';
			$data['post_parent'] = $old ? $old->post_parent : 0;
			return $data;
		}
		$data['menu_order'] = $state['order'];
		if ( empty( $postarr['post_name'] ) ) {
			$slug = $state['slug'];
			if ( 'excursus' === $meta['pg_kind'] ) {
				$slug = 'excursus-' . sanitize_title( wp_unslash( $data['post_title'] ) );
			}
			if ( $slug ) {
				$data['post_name'] = wp_unique_post_slug( $slug, $id, $data['post_status'], self::TYPE, (int) $data['post_parent'] );
				self::$expected_slugs[ $id ] = $slug;
			}
		} elseif ( preg_match( '/^\d+(?:-\d+)+$/D', $postarr['post_name'] ) ) {
			self::$expected_slugs[ $id ] = $postarr['post_name'];
		}
		return $data;
	}

	public static function rest_validate( $prepared, WP_REST_Request $request ) {
		$id = (int) $request['id'];
		$old = get_post( $id );
		$parent = (int) ( $prepared->post_parent ?? ( $old ? $old->post_parent : 0 ) );
		$meta = array_merge( self::metadata( $id ), (array) $request->get_param( 'meta' ) );
		$status = $prepared->post_status ?? ( $old ? $old->post_status : 'draft' );
		$state = self::validate( $id, $parent, $meta, $status );
		if ( is_wp_error( $state ) ) {
			$state->add_data( array( 'status' => 400 ) );
			return $state;
		}
		if ( $parent && ! empty( $meta['pg_book'] ) ) {
			return new WP_Error( 'pg_book', 'Il numero canonico appartiene soltanto al libro.', array( 'status' => 400 ) );
		}
		foreach ( array( 'pg_ref_start', 'pg_ref_end' ) as $key ) {
			if ( isset( $meta[ $key ] ) && (int) $meta[ $key ] !== (int) get_post_meta( $id, $key, true ) ) {
				return new WP_Error( 'pg_derived', 'Gli estremi del brano sono calcolati automaticamente.', array( 'status' => 400 ) );
			}
		}
		self::$rest_meta[ $id ] = $meta;
		$prepared->menu_order = $state['order'];
		if ( empty( $prepared->post_name ) && ( ! $old || '' === $old->post_name ) ) {
			$slug = $state['slug'];
			if ( 'excursus' === $meta['pg_kind'] ) {
				$slug = 'excursus-' . sanitize_title( $prepared->post_title ?? ( $old ? $old->post_title : '' ) );
			}
			if ( $slug ) {
				$prepared->post_name = $slug;
			}
		}
		if ( ! empty( $prepared->post_name ) ) {
			self::$expected_slugs[ $id ] = $prepared->post_name;
		}
		return $prepared;
	}

	public static function save_panel( int $id, WP_Post $post ): void {
		if ( isset( self::$rejected_panels[ $id ] ) || isset( self::$rejected_panels[0] ) ) {
			unset( self::$rejected_panels[ $id ], self::$rejected_panels[0] );
			return;
		}
		if ( self::$syncing || wp_is_post_revision( $id ) || wp_is_post_autosave( $id ) || ! current_user_can( 'edit_post', $id ) ) {
			return;
		}
		$meta = self::panel();
		if ( null === $meta ) {
			return;
		}
		$state = self::validate( $id, (int) $post->post_parent, $meta, $post->post_status );
		if ( is_wp_error( $state ) ) {
			self::notice( $state->get_error_message() . ' I dati del brano non sono stati salvati.' );
			return;
		}
		self::$syncing = true;
		foreach ( $meta as $key => $value ) {
			if ( 'pg_book' === $key && $post->post_parent ) {
				delete_post_meta( $id, $key );
			} else {
				update_post_meta( $id, $key, $value );
			}
		}
		self::$syncing = false;
		self::sync( $id );
	}

	/** Guard direct custom-field/CLI writes as well as the editorial panel. */
	public static function guard_meta( $check, int $id, string $key, $value ) {
		if ( self::$syncing || self::TYPE !== get_post_type( $id ) || ! str_starts_with( $key, 'pg_' ) ) {
			return $check;
		}
		$error = '';
		$parent = wp_get_post_parent_id( $id );
		$public = in_array( get_post_status( $id ), array( 'publish', 'future', 'private' ), true );
		$kind = self::$rest_meta[ $id ]['pg_kind'] ?? self::$rest_meta[0]['pg_kind'] ?? get_post_meta( $id, 'pg_kind', true );
		if ( 'pg_book' === $key && wp_get_post_parent_id( $id ) && 0 === (int) $value ) {
			return true; // REST may send the default; a child must not store its own book number.
		}
		if ( in_array( $key, array( 'pg_ref_start', 'pg_ref_end' ), true ) ) {
			$error = 'Gli estremi del brano sono calcolati automaticamente.';
		} elseif ( 'pg_book' === $key && ( $parent || ! is_numeric( $value ) || (int) $value < 0 || (int) $value > 66 || ( 0 === (int) $value && $public ) ) ) {
			$error = 'Il numero canonico (1–66) appartiene soltanto al libro.';
		} elseif ( 'pg_kind' === $key && ( ! is_string( $value ) || ! in_array( $value, array( '', 'introduzione', 'puntata', 'excursus' ), true ) || ( ! $parent && '' !== $value ) || ( $parent && $public && '' === $value ) ) ) {
			$error = 'Tipo di puntata non valido.';
		} elseif ( 'pg_ref' === $key && '' === $value && 'puntata' === $kind && $public ) {
			$error = 'Il riferimento di una puntata pubblicata non può essere vuoto.';
		} elseif ( 'pg_ref' === $key && '' !== $value && 'introduzione' === $kind ) {
			$error = 'Lascia vuoto il riferimento dell’introduzione.';
		} elseif ( 'pg_ref' === $key && '' !== $value ) {
			$range = self::parse_reference( is_scalar( $value ) ? (string) $value : '', (int) get_post_meta( wp_get_post_parent_id( $id ), 'pg_book', true ) );
			if ( is_wp_error( $range ) ) {
				$error = $range->get_error_message();
			}
		}
		if ( $error ) {
			self::notice( $error );
			return false;
		}
		return $check;
	}

	public static function meta_changed( $meta_id, int $id, string $key ): void {
		if ( in_array( $key, array( 'pg_book', 'pg_kind', 'pg_ref', 'pg_ref_start', 'pg_ref_end' ), true ) ) {
			self::sync( $id );
		}
	}

	public static function guard_delete( $check, int $id, string $key, $value, bool $delete_all ) {
		if ( null !== $check || self::$syncing || ! in_array( $key, array( 'pg_book', 'pg_kind', 'pg_ref', 'pg_ref_start', 'pg_ref_end' ), true ) ) {
			return $check;
		}
		if ( $delete_all ) {
			// Apply the per-post guard instead of blocking unrelated post types.
			global $wpdb;
			$query = $wpdb->prepare( "SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key = %s", $key );
			$serialized = maybe_serialize( $value );
			if ( '' !== $serialized && null !== $serialized && false !== $serialized ) {
				$query .= $wpdb->prepare( ' AND meta_value = %s', $serialized );
			}
			$deleted = false;
			foreach ( $wpdb->get_col( $query ) as $post_id ) {
				if ( delete_metadata( 'post', (int) $post_id, wp_slash( $key ), wp_slash( $value ) ) ) {
					$deleted = true;
				}
			}
			return $deleted;
		}
		if ( self::TYPE !== get_post_type( $id ) ) {
			return $check;
		}
		$post = get_post( $id );
		$meta = self::metadata( $id );
		$meta[ $key ] = 'pg_book' === $key ? 0 : '';
		$state = self::validate( $id, (int) $post->post_parent, $meta, $post->post_status );
		if ( in_array( $key, array( 'pg_ref_start', 'pg_ref_end' ), true ) || is_wp_error( $state ) ) {
			self::notice( 'Il metadato è necessario al commentario e non può essere eliminato.' );
			return false;
		}
		return $check;
	}

	public static function sync( int $id ): void {
		$post = get_post( $id );
		if ( self::$syncing || ! $post || self::TYPE !== $post->post_type || 'auto-draft' === $post->post_status ) {
			return;
		}
		$meta = self::metadata( $id );
		$state = self::validate( $id, (int) $post->post_parent, $meta, $post->post_status );
		if ( is_wp_error( $state ) ) {
			return;
		}
		self::$syncing = true;
		update_post_meta( $id, 'pg_ref_start', $state['start'] );
		update_post_meta( $id, 'pg_ref_end', $state['end'] );
		if ( $post->post_parent ) {
			delete_post_meta( $id, 'pg_book' );
		}
		$update = array( 'ID' => $id, 'menu_order' => $state['order'], 'edit_date' => true );
		$slug = $state['slug'];
		if ( 'excursus' === $meta['pg_kind'] ) {
			$slug = 'excursus-' . sanitize_title( $post->post_title );
		}
		if ( '' === $post->post_name && $slug ) {
			$update['post_name'] = $slug;
			self::$expected_slugs[ $id ] = $slug;
		}
		if ( (int) $post->menu_order !== $state['order'] || isset( $update['post_name'] ) ) {
			wp_update_post( $update );
		}
		self::$syncing = false;
		$expected = self::$expected_slugs[ $id ] ?? self::$expected_slugs[0] ?? null;
		unset( self::$expected_slugs[ $id ], self::$expected_slugs[0] );
		if ( $expected && get_post_field( 'post_name', $id ) !== $expected ) {
			self::notice( sprintf( 'WordPress ha modificato lo slug «%s» in «%s». Verifica che non esista già una puntata con lo stesso indirizzo.', $expected, get_post_field( 'post_name', $id ) ) );
		}
		if ( ! $post->post_parent ) {
			foreach ( get_posts( array( 'post_type' => self::TYPE, 'post_parent' => $id, 'post_status' => array( 'publish', 'future', 'draft', 'pending', 'private' ), 'numberposts' => -1, 'fields' => 'ids' ) ) as $child ) {
				self::sync( $child );
			}
		}
	}

	public static function after_insert( int $id ): void {
		self::sync( $id );
		unset( self::$rest_meta[ $id ], self::$rest_meta[0] );
	}

	public static function editor_assets(): void {
		$screen = get_current_screen();
		if ( $screen && self::TYPE === $screen->post_type && $screen->is_block_editor() ) {
			wp_enqueue_script( 'pg-commentario-editor', plugins_url( 'pg-commentario-editor.js', __FILE__ ), array( 'wp-data', 'wp-dom-ready' ), (string) filemtime( __DIR__ . '/pg-commentario-editor.js' ), true );
		}
	}

	public static function meta_box(): void {
		add_meta_box( 'pg-commentario', 'Libro e brano', array( self::class, 'render_meta_box' ), self::TYPE, 'normal' );
	}

	public static function render_meta_box( WP_Post $post ): void {
		$meta = self::metadata( $post->ID );
		wp_nonce_field( 'pg_commentario', 'pg_commentario_nonce' );
		echo '<p>Per un libro, lascia vuoti tipo e riferimento. Per una voce, seleziona il libro negli attributi e indica il tipo.</p>';
		echo '<p><label for="pg-book">Numero canonico del libro (Atti = 44)</label><br><input id="pg-book" name="pg_book" type="number" min="1" max="66" value="' . esc_attr( $meta['pg_book'] ?: '' ) . '"></p>';
		echo '<p><label for="pg-kind">Tipo</label><br><select id="pg-kind" name="pg_kind">';
		foreach ( array( '' => 'Libro', 'introduzione' => 'Introduzione', 'puntata' => 'Puntata', 'excursus' => 'Excursus' ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $meta['pg_kind'], $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p><p><label for="pg-ref">Riferimento (senza il nome del libro)</label><br><input id="pg-ref" name="pg_ref" type="text" value="' . esc_attr( $meta['pg_ref'] ) . '" placeholder="1:1-14" aria-describedby="pg-ref-help"></p>';
		echo '<p id="pg-ref-help">Esempi: «1:1-14», «1:12-2:4». Lascia vuoto per l’introduzione; facoltativo per gli excursus. Ordine ed estremi del brano sono calcolati automaticamente. Gli slug esistenti restano invariati.</p>';
	}

	public static function archive_query( WP_Query $query ): void {
		if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( self::TYPE ) && ! $query->is_feed() ) {
			$query->set( 'post_parent', 0 );
			$query->set( 'orderby', array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) );
		}
	}

	private static function siblings( int $parent ): array {
		return get_posts( array( 'post_type' => self::TYPE, 'post_parent' => $parent, 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'ID' => 'ASC' ) ) );
	}

	private static function entry_link( WP_Post $post, bool $reference = false ): string {
		$ref = $reference ? get_post_meta( $post->ID, 'pg_ref', true ) : '';
		return '<a href="' . esc_url( get_permalink( $post ) ) . '">' . ( $ref ? '<span class="pg-reference">' . esc_html( $ref ) . '</span> — ' : '' ) . esc_html( get_the_title( $post ) ) . '</a>';
	}

	public static function content( string $content ): string {
		if ( ! is_singular( self::TYPE ) || ! in_the_loop() || ! is_main_query() || is_feed() || post_password_required() || get_the_ID() !== get_queried_object_id() ) {
			return $content;
		}
		$post = get_post();
		if ( ! $post->post_parent ) {
			$groups = array( 'introduzione' => array(), 'puntata' => array(), 'excursus' => array() );
			foreach ( self::siblings( $post->ID ) as $child ) {
				$kind = get_post_meta( $child->ID, 'pg_kind', true );
				if ( isset( $groups[ $kind ] ) ) {
					$groups[ $kind ][] = '<li>' . self::entry_link( $child, 'puntata' === $kind ) . '</li>';
				}
			}
			$html = '<nav class="pg-commentario-index" aria-label="Indice del libro"><h2>Indice del libro</h2>';
			foreach ( array( 'introduzione' => 'Introduzione', 'puntata' => 'Puntate', 'excursus' => 'Excursus' ) as $kind => $label ) {
				if ( $groups[ $kind ] ) {
					$html .= '<h3>' . $label . '</h3><ul>' . implode( '', $groups[ $kind ] ) . '</ul>';
				}
			}
			if ( ! array_filter( $groups ) ) {
				$html .= '<p>Le puntate saranno disponibili prossimamente.</p>';
			}
			return $content . $html . '</nav>';
		}
		$parent = get_post( $post->post_parent );
		if ( ! $parent || 'publish' !== $parent->post_status ) {
			return $content;
		}
		$html = '<nav class="pg-commentario-navigation" aria-label="Navigazione del commentario"><p><a href="' . esc_url( get_permalink( $parent ) ) . '">Indice del libro: ' . esc_html( get_the_title( $parent ) ) . '</a></p>';
		if ( 'excursus' !== get_post_meta( $post->ID, 'pg_kind', true ) ) {
			$chain = array_values( array_filter( self::siblings( $post->post_parent ), static function ( $child ) {
				return in_array( get_post_meta( $child->ID, 'pg_kind', true ), array( 'introduzione', 'puntata' ), true );
			} ) );
			$position = array_search( $post->ID, array_column( $chain, 'ID' ), true );
			if ( false !== $position ) {
				foreach ( array( -1 => 'Puntata precedente', 1 => 'Puntata successiva' ) as $offset => $label ) {
					if ( isset( $chain[ $position + $offset ] ) ) {
						$html .= '<p>' . $label . ': ' . self::entry_link( $chain[ $position + $offset ] ) . '</p>';
					}
				}
			}
		}
		return $content . $html . '</nav>';
	}

	/** Legacy draft URLs redirect only after publication; previews remain private. */
	public static function redirect(): void {
		if ( is_preview() || ! in_array( $_SERVER['REQUEST_METHOD'] ?? 'GET', array( 'GET', 'HEAD' ), true ) ) {
			return;
		}
		$map = get_option( 'pg_commentario_redirects', array() );
		$path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ), PHP_URL_PATH );
		$key = $path;
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $path === $home_path && isset( $_GET['p'] ) && is_scalar( $_GET['p'] ) && ctype_digit( (string) $_GET['p'] ) ) {
			$key = '?p=' . (int) $_GET['p'];
		}
		$id = (int) ( $map[ $key ] ?? 0 );
		if ( $id && self::TYPE === get_post_type( $id ) && 'publish' === get_post_status( $id ) && ! post_password_required( $id ) ) {
			wp_safe_redirect( get_permalink( $id ), 301, 'Per Grazia Commentario' );
			exit;
		}
	}
}

add_action( 'init', array( PG_Commentario::class, 'init' ) );
add_action( 'add_meta_boxes', array( PG_Commentario::class, 'meta_box' ) );
add_action( 'admin_notices', array( PG_Commentario::class, 'admin_notice' ) );
add_filter( 'wp_insert_post_data', array( PG_Commentario::class, 'insert_data' ), 10, 2 );
add_filter( 'rest_pre_insert_pg_commentario', array( PG_Commentario::class, 'rest_validate' ), 10, 2 );
add_action( 'save_post_pg_commentario', array( PG_Commentario::class, 'save_panel' ), 10, 2 );
add_action( 'wp_after_insert_post', array( PG_Commentario::class, 'after_insert' ) );
add_action( 'admin_enqueue_scripts', array( PG_Commentario::class, 'editor_assets' ) );
add_filter( 'add_post_metadata', array( PG_Commentario::class, 'guard_meta' ), 10, 4 );
add_filter( 'update_post_metadata', array( PG_Commentario::class, 'guard_meta' ), 10, 4 );
add_filter( 'delete_post_metadata', array( PG_Commentario::class, 'guard_delete' ), 10, 5 );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $pg_meta_hook ) {
	add_action( $pg_meta_hook, array( PG_Commentario::class, 'meta_changed' ), 10, 3 );
}
add_action( 'pre_get_posts', array( PG_Commentario::class, 'archive_query' ) );
add_filter( 'the_content', array( PG_Commentario::class, 'content' ), 20 );
add_action( 'template_redirect', array( PG_Commentario::class, 'redirect' ), 1 );

// Keep these fields in the validated panel; derived values must never be typed by hand.
add_filter( 'is_protected_meta', static function ( $protected, $key, $type ) {
	return 'post' === $type && in_array( $key, array( 'pg_book', 'pg_kind', 'pg_ref', 'pg_ref_start', 'pg_ref_end' ), true ) ? true : $protected;
}, 10, 3 );
