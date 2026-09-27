/* WordPress supplies React and the editor packages; no build or frontend script. */
(function (wp) {
	'use strict';
	const el = wp.element.createElement;
	const { __ } = wp.i18n;
	const { InspectorControls, useBlockProps } = wp.blockEditor;
	const { PanelBody, RangeControl, SelectControl, TextControl, ToggleControl, Placeholder } = wp.components;
	const ServerSideRender = wp.serverSideRender.default || wp.serverSideRender;
	const icon = el('svg', { viewBox: '0 0 24 24', xmlns: 'http://www.w3.org/2000/svg', 'aria-hidden': true },
		el('path', { fill: 'currentColor', d: 'M3 3h6v6H3zM11 3h10v2H11zM11 7h7v2h-7zM3 12h4v4H3zM9 12h12v2H9zM9 16h8v2H9zM3 20h4v2H3zM9 20h12v2H9z' })
	);

	wp.blocks.registerBlockType('brpw/recent-posts', {
		icon,
		edit({ attributes, setAttributes }) {
			const a = attributes;
			const blockProps = useBlockProps();
			// Free-text IDs remain available for sites with more than 100 terms/pages.
			const records = wp.data.useSelect((select) => ({
				categories: select('core').getEntityRecords('taxonomy', 'category', { per_page: 100, hide_empty: false, orderby: 'name', order: 'asc' }),
				pages: select('core').getEntityRecords('postType', 'page', { per_page: 100, status: 'publish', orderby: 'title', order: 'asc' })
			}), []);
			const update = (key) => (value) => setAttributes({ [key]: value });
			const selectControl = (key, label, options) => el(SelectControl, { key, label, value: a[key], options, onChange: update(key) });
			const toggles = [
				['show_image', __('Show featured images', 'beautiful-recent-posts-widget')],
				['show_date', __('Show date', 'beautiful-recent-posts-widget')],
				['show_author', __('Show author', 'beautiful-recent-posts-widget')],
				['show_comments', __('Show comment count', 'beautiful-recent-posts-widget')],
				['show_excerpt', __('Show excerpt', 'beautiful-recent-posts-widget')],
				['exclude_current', __('Exclude the current post', 'beautiful-recent-posts-widget')]
			].map(([key, label]) => el(ToggleControl, { key, label, checked: a[key], onChange: update(key) }));
			const categoryOptions = [{ label: __('All categories', 'beautiful-recent-posts-widget'), value: 0 }].concat((records.categories || []).map((term) => ({ label: term.name, value: term.id })));
			if (a.category && !categoryOptions.some((option) => option.value === a.category)) {
				categoryOptions.push({ label: `${__('Category ID', 'beautiful-recent-posts-widget')}: ${a.category}`, value: a.category });
			}
			const pageOptions = [{ label: __('No button destination', 'beautiful-recent-posts-widget'), value: 0 }].concat((records.pages || []).map((page) => ({ label: page.title.raw || page.slug, value: page.id })));
			if (a.pageid && !pageOptions.some((option) => option.value === a.pageid)) {
				pageOptions.push({ label: `${__('Page ID', 'beautiful-recent-posts-widget')}: ${a.pageid}`, value: a.pageid });
			}
			const empty = () => el(Placeholder, { icon, label: __('Beautiful Recent Posts', 'beautiful-recent-posts-widget') }, __('No published posts match these settings. Try another category or publish a post.', 'beautiful-recent-posts-widget'));
			return el(wp.element.Fragment, null,
				el(InspectorControls, null,
					el(PanelBody, { title: __('Posts and layout', 'beautiful-recent-posts-widget') },
						el(TextControl, { label: __('Title', 'beautiful-recent-posts-widget'), value: a.title, onChange: update('title') }),
						el(RangeControl, { label: __('Number of posts', 'beautiful-recent-posts-widget'), value: a.totalnews, min: 1, max: 20, onChange: update('totalnews') }),
						el(SelectControl, { label: __('Category', 'beautiful-recent-posts-widget'), value: a.category, options: categoryOptions, onChange: (value) => setAttributes({ category: Number(value) }) }),
						selectControl('orderby', __('Order posts by', 'beautiful-recent-posts-widget'), [
							{ label: __('Newest first', 'beautiful-recent-posts-widget'), value: 'date' },
							{ label: __('Recently updated', 'beautiful-recent-posts-widget'), value: 'modified' },
							{ label: __('Title A–Z', 'beautiful-recent-posts-widget'), value: 'title' }
						]),
						selectControl('layout', __('Layout', 'beautiful-recent-posts-widget'), [{ label: __('Editorial list', 'beautiful-recent-posts-widget'), value: 'list' }, { label: __('Cards', 'beautiful-recent-posts-widget'), value: 'cards' }]),
						a.layout === 'list' && a.show_image && selectControl('image_shape', __('Thumbnail shape', 'beautiful-recent-posts-widget'), [
							{ label: __('Circle', 'beautiful-recent-posts-widget'), value: 'circle' }, { label: __('Rounded', 'beautiful-recent-posts-widget'), value: 'rounded' }, { label: __('Square', 'beautiful-recent-posts-widget'), value: 'square' }
						])
					),
					el(PanelBody, { title: __('Display details', 'beautiful-recent-posts-widget'), initialOpen: false }, ...toggles,
						a.show_excerpt && el(RangeControl, { label: __('Excerpt length', 'beautiful-recent-posts-widget'), value: a.excerpt_length, min: 5, max: 60, onChange: update('excerpt_length') })
					),
					el(PanelBody, { title: __('Read more button', 'beautiful-recent-posts-widget'), initialOpen: false },
						el(TextControl, { label: __('Button text', 'beautiful-recent-posts-widget'), value: a.textbutton, onChange: update('textbutton') }),
						el(SelectControl, { label: __('Button destination', 'beautiful-recent-posts-widget'), value: a.pageid, options: pageOptions, onChange: (value) => setAttributes({ pageid: Number(value) }) })
					),
					el(PanelBody, { title: __('Advanced selection', 'beautiful-recent-posts-widget'), initialOpen: false },
						el(TextControl, { label: __('Category ID', 'beautiful-recent-posts-widget'), help: __('Use an ID if the category is missing from the first 100 results. Use 0 for all categories.', 'beautiful-recent-posts-widget'), type: 'number', min: 0, value: a.category, onChange: (value) => setAttributes({ category: Math.max(0, parseInt(value, 10) || 0) }) }),
						el(TextControl, { label: __('Button page ID', 'beautiful-recent-posts-widget'), help: __('Use an ID if the page is missing from the first 100 results.', 'beautiful-recent-posts-widget'), type: 'number', min: 0, value: a.pageid, onChange: (value) => setAttributes({ pageid: Math.max(0, parseInt(value, 10) || 0) }) })
					)
				),
				el('div', blockProps, el(ServerSideRender, { block: 'brpw/recent-posts', attributes, EmptyResponsePlaceholder: empty }))
			);
		},
		save() { return null; }
	});
})(window.wp);
