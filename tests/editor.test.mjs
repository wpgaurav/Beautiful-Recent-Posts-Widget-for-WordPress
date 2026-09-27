import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const metadata = JSON.parse(readFileSync(new URL('../blocks/recent-posts/block.json', import.meta.url)));
const source = readFileSync(new URL('../blocks/recent-posts/editor.js', import.meta.url), 'utf8');
function load(records = { categories: [], pages: [] }) {
	let block;
	const createElement = (type, props, ...children) => ({ type, props: props || {}, children: children.flat() });
	const wp = {
		element: { createElement, Fragment: 'Fragment' }, i18n: { __: (text) => text },
		blockEditor: { InspectorControls: 'InspectorControls', useBlockProps: () => ({ className: 'wp-block' }) },
		components: Object.fromEntries(['PanelBody', 'RangeControl', 'SelectControl', 'TextControl', 'ToggleControl', 'Placeholder'].map((key) => [key, key])),
		serverSideRender: { default: 'ServerSideRender' },
		data: { useSelect: () => records },
		blocks: { registerBlockType: (name, settings) => { block = { name, ...settings }; } }
	};
	vm.runInNewContext(source, { window: { wp } });
	return block;
}
function walk(node) { return node && typeof node === 'object' ? [node, ...node.children.flatMap(walk)] : []; }
const defaults = Object.fromEntries(Object.entries(metadata.attributes).map(([key, value]) => [key, value.default]));

test('registers the PHP metadata block and saves no stale markup', () => {
	const block = load();
	assert.equal(block.name, metadata.name);
	assert.equal(block.save(), null);
});
test('editor tolerates loading records and preserves numeric category values', () => {
	const block = load({ categories: null, pages: null });
	const changes = [];
	const tree = walk(block.edit({ attributes: defaults, setAttributes: (value) => changes.push(value) }));
	tree.find((node) => node.props.label === 'Category').props.onChange('42');
	assert.equal(changes[0].category, 42);
	assert.equal(tree.find((node) => node.type === 'ServerSideRender').props.block, metadata.name);
});
test('selected records beyond the first page stay represented', () => {
	const tree = walk(load().edit({ attributes: { ...defaults, category: 501, pageid: 502 }, setAttributes() {} }));
	assert.ok(tree.find((node) => node.props.label === 'Category').props.options.some((option) => option.value === 501));
	assert.ok(tree.find((node) => node.props.label === 'Button destination').props.options.some((option) => option.value === 502));
});
test('layout and display controls update actual block attributes', () => {
	const changes = [];
	const tree = walk(load().edit({ attributes: { ...defaults, show_excerpt: true }, setAttributes: (value) => changes.push(value) }));
	tree.find((node) => node.props.label === 'Layout').props.onChange('cards');
	tree.find((node) => node.props.label === 'Show date').props.onChange(false);
	tree.find((node) => node.props.label === 'Excerpt length').props.onChange(35);
	assert.equal(changes[0].layout, 'cards');
	assert.equal(changes[1].show_date, false);
	assert.equal(changes[2].excerpt_length, 35);
});
