( () => {
	const toggle = document.querySelector( '.menu-toggle' );
	const navigation = document.querySelector( '#primary-navigation' );

	if ( ! toggle || ! navigation ) {
		return;
	}

	const closeMenu = () => {
		toggle.setAttribute( 'aria-expanded', 'false' );
		navigation.classList.remove( 'is-open' );
		document.body.classList.remove( 'menu-open' );
	};

	toggle.addEventListener( 'click', () => {
		const isOpen = toggle.getAttribute( 'aria-expanded' ) === 'true';
		toggle.setAttribute( 'aria-expanded', String( ! isOpen ) );
		navigation.classList.toggle( 'is-open', ! isOpen );
		document.body.classList.toggle( 'menu-open', ! isOpen );
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			closeMenu();
			toggle.focus();
		}
	} );

	window.addEventListener( 'resize', () => {
		if ( window.matchMedia( '(min-width: 54.01rem)' ).matches ) {
			closeMenu();
		}
	} );
} )();
