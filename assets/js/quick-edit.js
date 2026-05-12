/**
 * Populate Stickify quick edit field from row data.
 */
/* global inlineEditPost */
document.addEventListener( 'DOMContentLoaded', () => {
	if ( 'undefined' === typeof inlineEditPost ) {
		return;
	}

	const edit = inlineEditPost.edit;

	inlineEditPost.edit = function ( id ) {
		edit.apply( this, arguments );

		let postId = 0;

		if ( 'object' === typeof id ) {
			postId = parseInt( this.getId( id ), 10 );
		} else {
			postId = parseInt( id, 10 );
		}

		if ( ! postId ) {
			return;
		}

		const postRow = document.getElementById( `post-${ postId }` );
		const dataEl = postRow?.querySelector( '.stickify-quick-edit-data' );
		const isStickified = 1 === Number( dataEl?.dataset.stickify );

		const editRow = document.getElementById( `edit-${ postId }` );

		const touched = editRow?.querySelector(
			'input[name="stickify_quick_edit_touched"]'
		);
		if ( touched ) {
			touched.value = '0';
		}

		const checkbox = editRow?.querySelector(
			'input[name="stickify_quick_edit"]'
		);
		if ( checkbox ) {
			checkbox.checked = isStickified;
		}
	};

	document.addEventListener( 'change', ( event ) => {
		if ( event.target.matches( 'input[name="stickify_quick_edit"]' ) ) {
			const col = event.target.closest( '.inline-edit-col' );
			const touched = col?.querySelector(
				'input[name="stickify_quick_edit_touched"]'
			);
			if ( touched ) {
				touched.value = '1';
			}
		}
	} );
} );
