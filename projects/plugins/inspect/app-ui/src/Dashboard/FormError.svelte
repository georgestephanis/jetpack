<script lang="ts">
	export let error: { _errors: string[] } | false = false;
</script>

{#if error}
	{#each error._errors as message (message)}
		<div class="err">{message}</div>
	{/each}
{/if}

<style>
	.err {
		padding: 10px;
		background: hsl( 25, 94%, 94% );
		color: #000;
		margin: 0;
		margin-bottom: 20px;
	}
</style>
