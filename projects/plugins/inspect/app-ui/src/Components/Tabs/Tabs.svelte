<script context="module">
	export const TABS = {};
</script>

<script>
	/* eslint-disable import/no-duplicates -- https://github.com/import-js/eslint-plugin-import/issues/2992 */
	import { setContext, onDestroy } from 'svelte';
	import { cubicInOut } from 'svelte/easing';
	import { writable } from 'svelte/store';
	import { slide } from 'svelte/transition';
	/* eslint-enable import/no-duplicates */

	const tabs = [];
	const panels = [];
	const selectedTab = writable( null );
	const selectedPanel = writable( null );

	setContext( TABS, {
		registerTab: tab => {
			tabs.push( tab );
			selectedTab.update( current => current || tab );

			onDestroy( () => {
				const i = tabs.indexOf( tab );
				tabs.splice( i, 1 );
				selectedTab.update( current =>
					current === tab ? tabs[ i ] || tabs[ tabs.length - 1 ] : current
				);
			} );
		},

		registerPanel: panel => {
			panels.push( panel );
			selectedPanel.update( current => current || panel );

			onDestroy( () => {
				const i = panels.indexOf( panel );
				panels.splice( i, 1 );
				selectedPanel.update( current =>
					current === panel ? panels[ i ] || panels[ panels.length - 1 ] : current
				);
			} );
		},

		selectTab: tab => {
			const i = tabs.indexOf( tab );
			selectedTab.set( tab );
			selectedPanel.set( panels[ i ] );
		},

		selectedTab,
		selectedPanel,
	} );
</script>

<div class="tabs" transition:slide={{ easing: cubicInOut, duration: 200 }}>
	<slot />
</div>

<style>
	.tabs {
		padding: 20px;
	}
</style>
