/* Keep the legacy panel in the block editor's REST save, including first publication. */
wp.domReady(function () {
	const book = document.getElementById('pg-book');
	const kind = document.getElementById('pg-kind');
	const reference = document.getElementById('pg-ref');
	if (!book || !kind || !reference || !wp.data.select('core/editor')) return;
	const sync = function () {
		wp.data.dispatch('core/editor').editPost({
			meta: {
				pg_book: book.value ? Number(book.value) : 0,
				pg_kind: kind.value,
				pg_ref: reference.value.trim()
			}
		});
	};
	[book, kind, reference].forEach(function (field) {
		field.addEventListener('change', sync);
		field.addEventListener('input', sync);
	});
});
